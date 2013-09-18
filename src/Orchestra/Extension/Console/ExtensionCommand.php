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
	public function fire()
	{
		$fired = true;
		$name  = $this->argument('name');

		switch ($action = $this->argument('action'))
		{
			case 'install' :
				# passthru;
			case 'upgrade' :
				$this->fireMigration();
				$this->info('orchestra/extension has been migrated');
				break;
			case 'update' :
				$this->firePublisher($name);
				break;
			case 'detect' :
				$this->fireDetect();
				break;
			case 'activate' :
				$this->fireActivate($name);
				break;
			case 'deactivate' :
				$this->fireDeactivate($name);
				break;
			default :
				$fired = false;
				break;
		}

		if ($fired === false) return $this->error("Invalid action [{$action}].");

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
