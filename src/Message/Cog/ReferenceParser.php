<?php

namespace Message\Cog;

/**
 * This method parses a view/controller reference and provides an easy API
 * to access the various parts of the reference, and outputs paths as required
 * elsewhere in the system.
 *
 * It supports a 'relative marker' that will find the current vendor/module and
 * automatically use this.
 *
 * Examples:
 *
 * * ::ClassName#methodName
 * * ModuleBundleName:Core:DirectoryName:ClassName#methodName
 * * ModuleName:DirectoryName:ClassName#methodName
 * * Bespoke:Wishlist:FolderName:ViewName
 * * ::DirectoryName:ViewName
 *
 * @author Joe Holdcroft <joe@message.uk.com>
 */
class ReferenceParser implements ReferenceParserInterface
{
	const SEPARATOR        = ':';
	const METHOD_SEPARATOR = '#';
	const RELATIVE_MARKER  = '::';

	protected $_fnsUtility;

	protected $_reference;

	protected $_vendor;
	protected $_module;
	protected $_path;
	protected $_method;

	/**
	 * Constructor.
	 *
	 * @param Functions\Utility $fnsUtility Function class for utility functions
	 */
	public function __construct($fnsUtility)
	{
		$this->_fnsUtility = $fnsUtility;
	}

	/**
	 * Get a Symfony "logical controller name" from this reference.
	 *
	 * This is used in the Routing component. Symfony "logical controller name"s
	 * are formatted as follows:
	 *
	 * [full class name]::[method name]
	 *
	 * @return string The rendered Symfony "logical controller name"
	 */
	public function getSymfonyLogicalControllerName()
	{
		$this->_checkEmpty();

		return $this->getClassName('Controller') . '::' . $this->_method;
	}

	/**
	 * Get the full path to the file referenced.
	 *
	 * Allows a namespace to be passed that will be inserted after the
	 * module name and before the reference's path.
	 *
	 * @param  null|string|array $pathNamespace The namespace to use (array if more than one directory)
	 * @return string                           The full path to the file
	 */
	public function getFullPath($pathNamespace = null, $separator = null)
	{
		$this->_checkEmpty();

		if (is_null($separator)) {
			$separator = DIRECTORY_SEPARATOR;
		}

		// Make sure the path namespace is an array
		if (!is_array($pathNamespace)) {
			$pathNamespace = array($pathNamespace);
		}

		// Build and return the full path
		return implode($separator, array_filter(array(
			$this->_vendor,
			$this->_module,
			implode($separator, $pathNamespace),
			implode($separator, $this->_path),
		)));
	}

	/**
	 * Get the full class name (with namespaces) for the reference.
	 *
	 * Allows a namespace to be passed that will be inserted after the
	 * module name and before the reference's path.
	 *
	 * @param  null|string|array $pathNamespace The namespace to use (array if more than one directory)
	 * @return string                           The full class name
	 */
	public function getClassName($pathNamespace = null)
	{
		return $this->getFullPath($pathNamespace, '\\');
	}

	/**
	 * Get all parts of the reference as an associative array.
	 *
	 * Keys returned:
	 * * vendor
	 * * module
	 * * path
	 * * method
	 *
	 * @return array All parts as an associative array
	 */
	public function getAllParts()
	{
		$this->_checkEmpty();

		return array(
			'vendor' => $this->_vendor,
			'module' => $this->_module,
			'path'   => $this->_path,
			'method' => $this->_method,
		);
	}

	/**
	 * Checks whether the reference parsed used a relative marker.
	 *
	 * @return boolean Result of the check
	 */
	public function isRelative()
	{
		$this->_checkEmpty();

		return self::RELATIVE_MARKER === substr($this->_reference, 0, strlen(self::RELATIVE_MARKER));
	}

	/**
	 * Parses a reference and returns an instance of this class with the parsed
	 * reference so that the various accessors can be used immediately.
	 *
	 * @param  string $reference The reference to be parsed
	 *
	 * @return ReferenceParser   Returns self for method chaning
	 */
	public function parse($reference)
	{
		$this->clear();
		// Save original reference
		$this->_reference = $reference;
		// Parse the method
		$this->_parseMethod();
		// Parse the vendor and module name
		$this->_parseVendorAndModule();
		// Get the path
		$this->_parsePath();

		return $this;
	}

	public function clear()
	{
		$this->_vendor = null;
		$this->_module = null;
		$this->_path   = null;
		$this->_method = null;
	}

	/**
	 * Parses the method from the reference and sets it on $this->_method.
	 *
	 * @return boolean Returns true if a method was parsed
	 */
	protected function _parseMethod()
	{
		if (false !== $methodPos = strrpos($this->_reference, self::METHOD_SEPARATOR)) {
			return $this->_method = substr($this->_reference, $methodPos + 1);
		}

		return false;
	}

	/**
	 * Parses the vendor and module from the reference and sets them as
	 * $this->_vendor and $this->_module.
	 *
	 * If the reference uses a relative marker, this executes a backtrace to
	 * find the calling class's module and vendor names.
	 *
	 * @throws \LogicException If the vendor and module name could not be determined
	 */
	protected function _parseVendorAndModule()
	{
		if ($this->isRelative()) {
			// Fill in the current vendor and module
			$fullModuleName = explode('\\', $this->_fnsUtility->traceCallingModuleName());
		}
		else {
			// Find the full module name (vendor and module) by getting text
			// before the second separator
			$firstSeparatorPos  = strpos($this->_reference, self::SEPARATOR);
			$secondSeparatorPos = strpos($this->_reference, self::SEPARATOR, $firstSeparatorPos + 1);
			$fullModuleName     = explode(
				self::SEPARATOR,
				substr($this->_reference, 0, $secondSeparatorPos + 1)
			);
		}

		// Remove any empty elements from the full module name array
		$fullModuleName = array_filter($fullModuleName);

		// Check we have both vendor and module name
		if (!is_array($fullModuleName) || 2 !== count($fullModuleName)) {
			throw new \InvalidArgumentException(
				sprintf('Vendor and module name could not be determined from reference: `%s`', $this->_reference)
			);
		}

		// Assign the vendor and module name
		list($this->_vendor, $this->_module) = $fullModuleName;
	}

	/**
	 * Parses the path from a method and sets is as an array on $this->_path
	 * so it can easily be traversed and manipulated.
	 */
	protected function _parsePath()
	{
		$reference = $this->_reference;

		// Remove method
		if ($this->_method) {
			$reference = substr(
				$reference,
				0,
				strlen($reference) - strlen(self::METHOD_SEPARATOR . $this->_method)
			);
		}

		// Determine start position for path
		if ($this->isRelative()) {
			$startPos = strlen(self::RELATIVE_MARKER);
		}
		else {
			$startPos = strlen(implode(self::SEPARATOR, array(
				$this->_vendor,
				$this->_module
			)) . self::SEPARATOR);
		}

		// Remove relative indicator or vendor/module name
		$reference = substr($reference, $startPos);

		// What's left is the path, set this as an array
		$this->_path = explode(self::SEPARATOR, $reference);
	}

	/**
	 * Throws an exception is no reference has been parsed yet. This is used by
	 * getter methods on this class.
	 *
	 * @throws \RuntimeException If nothing has been successfully parsed yet
	 */
	protected function _checkEmpty()
	{
		if (is_null($this->_reference)) {
			throw new \RuntimeException('No reference has been parsed yet.');
		}
	}
}