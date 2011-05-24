<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Doctrine extends Controller
{
	//Location of CLI tool from application folder
	private $_cli = 'modules/doctrine2/bin/doctrine';

	public function before()
	{
		parent::before();

		//Restrict controller to localhost or script access
		if ( isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], array('::1', '127.0.0.1')) )
		{
			echo "DENIED!";
			exit;
		}
	}

	function action_index()
	{
		echo View::factory('doctrine/doctrine');

		foreach ( $_POST as $action => $str )
			$this->_process_action( $action );
	}

	/*
	 * Called by the Kohana CLI tool (which in turn was called by Doctrine CLI tool)
	 * Returns specified DB info as a string
	 */
	public function action_db( $conn_name, $info_name )
	{
		$dbconfs = Kohana::config('database');

		if ( $info_name == 'type' )
			$this->request->response = @$dbconfs[ $conn_name ][ $info_name ];
		else
			$this->request->response = @$dbconfs[ $conn_name ]['connection'][ $info_name ];
	}

	/*
	 * Process a requestand perform any necessary actions
	 */
	private function _process_action( $action )
	{
		switch ( $action )
		{
			//Validate DB schema
			case 'validate':
				$this->_exececho(
					'Validation Errors:',
					$this->_cli . " orm:validate-schema"
				);
				break;

			//Generate entities, proxies and repositories
			case 'schema':
				/*
				 * This is AWFUL but required until one of two bugs in the entity
				 * manager is fixed.
				 *
				 * Firstly, annotations aren't updated if you don't have the
				 * --regenerate-entities=1 argument isn't preseent in the
				 * orm:generate-entities command
				 *
				 * Secondly, 50% of the time, blank entities will be generated if
				 * --regenerate-entities=1 IS present in the orm:generate-entities
				 * command.
				 *
				 * Catch 22.
				 *
				$this->_exececho(
					'Deleting entities',
					"rm models/*.php"
				);
				*/

				$this->_exececho(
					'Generating entities',
					$this->_cli . " orm:generate-entities --generate-annotations=1 ./application/"
				);

				$this->_exececho(
					'<br/><br/>Generating proxies',
					$this->_cli . " orm:generate-proxies ./application/models/proxies --quiet"
				);

				$this->_exececho(
					'<br/><br/>Generating repositories',
					$this->_cli . " orm:generate-repositories ./application/"
				);
				break;

			//Show SQL for creating/updating DB
			case 'tables-sql':
				$this->_exececho(
					'<br/><br/>Determining DB modifications',
					$this->_cli . " orm:schema-tool:update --dump-sql"
				);
				break;

			//Create/update DB
			case 'tables':
				$this->_exececho(
					'<br/><br/>Creating/Updating DB',
					$this->_cli . " orm:schema-tool:update --force"
				);
				break;

			/* //Load data fixtures - NOT AVAILABLE IN D2
			case 'data':
				Doctrine_Manager::connection()->execute("
					SET FOREIGN_KEY_CHECKS = 0
				");

				Doctrine::loadData(APPPATH . DIRECTORY_SEPARATOR . 'doctrine/fixtures/data');
				echo "Done!";
				break;
			*/

			default:
				echo 'Invalid action: ' . $action;
				break;
		}
	}

	//Perform specified command using the doctrine 2 CLI
	private function _exececho( $title, $command )
	{
		echo '<strong>',$title,'</strong><br/>';
		echo $command;
		exec( $command, $output );
		foreach ( $output as $line )
			echo $line, '<br/>';
	}

} // End Welcome
