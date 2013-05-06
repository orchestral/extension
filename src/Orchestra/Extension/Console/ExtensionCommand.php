<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExtensionCommand extends Command {
	
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'orchestra:extension';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Orchestra\Extension commandline tool';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		switch ($action = $this->argument('action'))
		{
			case 'install' :
			case 'upgrade' :
				$this->fireMigration();
				$this->info('orchestra/extension has been migrated');
				break;
			case 'detect' :
				$this->fireDetectExtension();
				break;
			default :
				$this->error("Invalid action [{$action}].");
		}
	}

	/**
	 * Fire migration process.
	 *
	 * @access protected
	 * @return void
	 */
	protected function fireMigration()
	{
		$this->call('migrate', array('--package' => 'orchestra/memory'));
	}

	/**
	 * Fire extension detection.
	 *
	 * @access protected
	 * @return void
	 */
	protected function fireMigration()
	{
		$extensions = $this->laravel['orchestra.extension.finder']->detect();

		if ( ! empty($extensions)) $this->line("<info>Detected:</info>");

		foreach ($extension as $name => $options)
		{
			$this->line("<comment>{$name}</comment>");
		}
	}
				

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('action', InputArgument::REQUIRED, "Type of action. E.g: 'install', 'upgrade'."),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array();
	}
}
