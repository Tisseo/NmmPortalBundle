<?php

namespace CanalTP\NmmPortalBundle\Migrations\pdo_pgsql;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Guzzle\Http\Client;

class Version003 extends AbstractMigration implements ContainerAwareInterface
{
    const VERSION = '0.0.3';
    const NMM_ORIGIN = 'nmm';

    /*
     * @var Client
     */
    private $fenrirClient;

    /*
     * @var array
     */
    private $fenrirOrigins;

    /*
     * @var ContainerInterface
     */
    private $container;

    public function getName()
    {
        return self::VERSION;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
    * @param Schema $schema
    */
    public function preDown(Schema $schema)
    {
        $this->fenrirClient = $this->container->get('canal_tp_fenrir.api');
    }

    /**
    * @param Schema $schema
    */
    public function preUp(Schema $schema)
    {
        $this->fenrirClient = $this->container->get('canal_tp_fenrir.api');
    }

    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE public.tr_customer_cus ADD COLUMN fenrir_id INT NULL;');
        $this->addSql('ALTER TABLE public.tr_customer_cus ADD UNIQUE (fenrir_id);');

        /* Create NMM origin with FenrirAPI */
        $this->createNmmOrigin();

        /* Update fenrir_id field in tr_customer_cus (NMM) */
        $this->createFenrirUsersAndUpdateFenrirId();
    }

    private function createNmmOrigin()
    {
        $idOrigin = $this->fenrirClient->postOrigin(self::NMM_ORIGIN);
        $this->fenrirOrigins[self::NMM_ORIGIN] = $idOrigin;
    }

    private function createFenrirUsersAndUpdateFenrirId()
    {
        foreach ($this->getCustomers() as $customer) {
            $user = $this->fenrirClient->postUser(
                $customer['cus_identifier'],
                $this->fenrirOrigins[self::NMM_ORIGIN]
            );

            $this->addSql("UPDATE public.tr_customer_cus "
                . "SET fenrir_id = ".$user->id." "
                . "WHERE cus_identifier = '".$customer['cus_identifier']."';");
        }
    }

    private function getCustomers()
    {
        $statement = $this->connection->prepare("SELECT cus_identifier FROM public.tr_customer_cus;");
        $statement->execute();

        return $statement->fetchAll();
    }

    public function down(Schema $schema)
    {
        $this->addSql('ALTER TABLE public.tr_customer_cus DROP COLUMN fenrir_id;');

        foreach ($this->fenrirClient->getUsers() as $user) {
            if ($user->origin->name == self::NMM_ORIGIN) {
                $this->fenrirClient->deleteUser($user->id);
            }
        }

        $origins = $this->fenrirClient->getOrigins();

        foreach ($origins as $origin) {
            if ($origin->name == self::NMM_ORIGIN) {
                $this->fenrirClient->deleteOrigin($origin->id);
            }
        }
    }
}
