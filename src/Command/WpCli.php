<?php # -*- coding: utf-8 -*-

namespace WpProvision\Command;

use WpProvision\Env\Shell;
use WpProvision\Process\ProcessBuilder;
use WpProvision\Process\SymfonyProcessBuilderAdapter;
use LogicException;

/**
 * Wrapper for WP-CLI command
 *
 * Usage example: To execute `wp site list` just run
 *
 * ( new WpCli() )->run( 'site list' );
 *
 * @package WpProvision\Command
 */
final class WpCli implements WpCliCommand {

	/**
	 * @var string
	 */
	private $base;

	/**
	 * @var string
	 */
	private $bin_path;

	/**
	 * @var Shell
	 */
	private $shell;

	/**
	 * @var ProcessBuilder
	 */
	private $process_builder;

	/**
	 * @param Shell $shell
	 * @param string $bin_path
	 * @param ProcessBuilder $process_builder
	 */
	public function __construct(
		Shell $shell,
		$bin_path = '',
		ProcessBuilder $process_builder = NULL
	) {

		/**
		 * Todo: remove this. We should rely only on paths to binaries provided by composer
		 * and not on global installed versions of WP-CLI.
		 */
		$this->shell = $shell;
		if ( $bin_path ) {
			$this->bin_path = realpath( $bin_path );
			$this->base = $bin_path;
		} else {
			$this->base = 'wp';
		}

		if ( ! $process_builder ) {
			$process_builder = new SymfonyProcessBuilderAdapter();
		}
		$this->process_builder = $process_builder;

		/**
		 * This sucks as we cannot know if the process builder is used elsewhere.
		 * Not sure if we should clone the object here or if we need an object-builder builder.
		 */
		$this->process_builder->setPrefix( $this->base() );
	}

	/**
	 * @return string
	 */
	public function base() {

		return $this->base;
	}

	/**
	 * @return bool
	 */
	public function commandExists() {

		if ( $this->bin_path ) {
			return $this->shell->isExecutable( $this->bin_path );
		}

		return $this->shell->commandExists( $this->base );
	}

	/**
	 * @param array $arguments
	 *
	 * @return string
	 */
	public function run( array $arguments = [] ) {

		if ( ! $this->commandExists() )
			throw new LogicException( "The base command {$this->base()} does not exists or is not executable." );

		$process = $this
			->process_builder
			->setArguments( [] ) // reset the process builder state
			->setArguments( $arguments )
			->getProcess()
			->mustRun();

		return $process->getOutput();
	}

}
