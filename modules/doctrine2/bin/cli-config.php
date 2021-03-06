<?php
/**
 *
 *	@author Flynsarmy <www.flynsarmy.com>
 *	@version 1.02
 *  @license http://www.apache.org/licenses/LICENSE-2.0 Apache Licence 2.0
 */
//This is the db connection name from the db config
define( 'DB_CONN', 'default' );
define( 'SYSPATH', realpath(__DIR__.'/../../../').'/');
define( 'APPPATH', SYSPATH.'application/');

require_once __DIR__.'/../classes/vendor/doctrine/Doctrine/Common/ClassLoader.php';

$classLoader = new \Doctrine\Common\ClassLoader('Doctrine', __DIR__.'/../classes/vendor/doctrine');
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('Symfony', __DIR__.'/../classes/vendor/doctrine/Doctrine');
$classLoader->register();
$classLoader = new \Doctrine\Common\ClassLoader('models', rtrim(APPPATH, '/'));
$classLoader->register();

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
//$driver = $config->newDefaultAnnotationDriver( APPPATH.'models' );
$driver = new \Doctrine\ORM\Mapping\Driver\YamlDriver(array(APPPATH.'models/fixtures/schema'));
$driver->setFileExtension('.yml');
$config->setMetadataDriverImpl( $driver );

$config->setProxyDir( APPPATH.'models/proxies' );
$config->setProxyNamespace('models\proxies');

$connectionOptions = array(
    'driver' 	=> 'pdo_'.get_db_info( DB_CONN, 'type' ),
	'dbname' 	=> get_db_info( DB_CONN, 'database' ),
	'user' 		=> get_db_info( DB_CONN, 'username' ),
	'password' 	=> get_db_info( DB_CONN, 'password' ),
	'host' 		=> get_db_info( DB_CONN, 'hostname' ),
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

$helpers = array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);

//Run the Kohana CLI tool to access our doctrine controller which returns DB info
function get_db_info( $conn_name, $info_name ) {
	exec("php ".SYSPATH."index.php --uri=doctrine/db/$conn_name/$info_name", $output);

	return empty($output[0]) ? NULL : $output[0];
}