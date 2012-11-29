<?php

namespace Message\Cog\Bootstrap;

use Message\Cog\Service\ContainerInterface;
use Message\Cog\Service\ContainerAwareInterface;

/**
 * Bootstrap loader, responsible for loading bootstraps from modules or Cog
 * itself.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Loader
{
	protected $_services;
	protected $_bootstraps = array();

	/**
	 * Constructor.
	 *
	 * @param ContainerInterface $container The service container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->_services = $container;
	}

	/**
	 * Load all bootstrap files from a given directory. This does *not* support
	 * recursive loading.
	 *
	 * This will add any classes in the directory that extend the
	 * `BootstrapInterface` interface to this loader.
	 *
	 * @param  string $path      The directory to load from
	 * @param  string $namespace The namespace for this directory
	 *
	 * @return Loader            Returns $this for chaining
	 */
	public function addFromDirectory($path, $namespace)
	{
		// Check the leading namespace slash is there
		if ('\\' !== substr($namespace, 0, 1)) {
			$namespace = '\\' . $namespace;
		}

		if (is_dir($path)) {
			$dir = new \DirectoryIterator($path);
			foreach ($dir as $file) {
				// Skip non-php files
				if (!$file->valid() || 'php' !== $file->getExtension()) {
					continue;
				}
				// Determine class name
				$className = $namespace . '\\' . $file->getBasename('.php');
				// Load the bootstrap
				$class = new $className;
				if ($class instanceof BootstrapInterface) {
					$this->add($class);
				}
			}
		}

		return $this;
	}

	/**
	 * Adds a bootstrap this loader.
	 *
	 * If the bootstrap implements the `ContainerAwareInterface`, the service
	 * container is injected as defined in the interface.
	 *
	 * @param  BootstrapInterface $bootstrap The bootstrap to add
	 *
	 * @return Loader                        Returns $this for chaining
	 */
	public function add(BootstrapInterface $bootstrap)
	{
		// If the bootstrap is ContainerAware, set the container
		if ($bootstrap instanceof ContainerAwareInterface) {
			$bootstrap->setContainer($this->_services);
		}

		// Add to list
		$this->_bootstraps[] = $bootstrap;

		return $this;
	}

	/**
	 * Load all of the bootstraps that have been added to this loader.
	 *
	 * This loads all of the bootstraps that extend `ServicesInterface` first,
	 * because these are often used by event, route or task definitions.
	 *
	 * Routes are loaded second, then events and tasks are registered
	 * simultaneously after this.
	 *
	 * Once complete, all the bootstraps are removed from this loader as they
	 * have now been loaded.
	 */
	public function load()
	{
		// Register services first
		foreach ($this->_bootstraps as $bootstrap) {
			if ($bootstrap instanceof ServicesInterface) {
				$bootstrap->registerServices($this->_services);
			}
		}

		// Register routes second
		foreach ($this->_bootstraps as $bootstrap) {
			if ($bootstrap instanceof RoutesInterface) {
				$bootstrap->registerRoutes($this->_services['router']);
			}
		}

		// Register events and tasks last
		foreach ($this->_bootstraps as $bootstrap) {
			if ($bootstrap instanceof EventsInterface) {
				$bootstrap->registerEvents($this->_services['event.dispatcher']);
			}
			if ($this->_services['environment']->context() == 'console'
			 && $bootstrap instanceof TasksInterface) {
				$bootstrap->registerTasks($this->_services['task.collection']);
			}
		}

		// Clear the bootstrap list
		$this->clear();
	}

	/**
	 * Clear all bootstraps from this loader.
	 *
	 * @return Loader Returns $this for chaining
	 */
	public function clear()
	{
		$this->_bootstraps = array();

		return $this;
	}
}