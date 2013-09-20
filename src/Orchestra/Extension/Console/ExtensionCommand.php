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
	protected $description = 'Orchestra\Extension Command';

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
	public function run()
	{
		$this->execute();
		$this->finish();
	}

	/**
	 * Execute the console command.
	 * 
	 * @return mixed
	 */
	protected function execute()
	{
		$name  = $this->argument('name');

		switch ($action = $this->argument('action'))
		{
			case 'install' :
				# passthru;
			case 'upgrade' :
				// Both "install" and "upgrade" should run migration up for 
				// the orchestra/extension.
				return $this->fireMigration();

			case 'update' :
				// Running "update" would trigger publishing asset and migration 
				// for the extension.
				return $this->firePublisher($name);

			case 'detect' :
				// Running "detect" would trigger detecting changes to extensions.
				return $this->fireDetect();

			case 'activate' :
				// Running "activate" would trigger activation of an extension.
				return $this->fireActivate($name);

			case 'deactivate' :
				// Running "deactivate" would trigger deactivation of an extension.
				return $this->fireDeactivate($name);

			default :
				// If none of the action is triggered, we should notify the error 
				// to user.
				return $this->error("Invalid action [{$action}].");
		}
	}

	/**
	 * Finish the console command.
	 * 
	 * @return void
	 */
	protected function finish()
	{
		// Save any changes to orchestra/memory
		$this->laravel['orchestra.memory']->finish();
	}

	/**
	 * Fire migration process.
	 *
	 * @return void
	 */
	protected function fireMigration()
	{
		$this->call('migrate', array('--package' => 'orchestra/memory'));
		$this->info('orchestra/extension has been migrated');
	}

	/**
	 * Fire extension detection.
	 *
	 * @return void
	 */
	protected function fireDetect()
	{
		$service    = $this->laravel['orchestra.extension'];
		$extensions = $service->detect();

		if (empty($extensions))
		{
			return $this->line("<comment>No extension detected!</comment>");
		}

		$this->line("<info>Detected:</info>");

		foreach ($extensions as $name => $options)
		{
			$output = ($service->started($name) ? "✓ <info>%s [%s]</info>" : "- <comment>%s [%s]</comment>");
			
			$this->line(sprintf($output, $name, $options['version']));
		}
	}

	/**
	 * Fire extension activation.
	 *
	 * @param  string   $name
	 * @return void
	 */
	protected function fireActivate($name)
	{
		$this->laravel['orchestra.extension']->activate($name);
		$this->info("Extension [{$name}] activated.");
	}

	/**
	 * Update an extension.
	 *
	 * @param  string   $name
	 * @return void
	 */
	protected function firePublisher($name)
	{
		$this->laravel['orchestra.extension']->publish($name);
		$this->info("Extension [{$name}] updated.");
	}

	/**
	 * Fire extension activation.
	 *
	 * @param  string   $name
	 * @return void
	 */
	protected function fireDeactivate($name)
	{
		$this->laravel['orchestra.extension']->deactivate($name);
		$this->info("Extension [{$name}] deactivated.");
	}
				

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('action', InputArgument::OPTIONAL, "Type of action, e.g: 'install', 'upgrade', 'detect', 'activate', 'deactivate'."),
			array('name', InputArgument::OPTIONAL, 'Extension Name.'),
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
