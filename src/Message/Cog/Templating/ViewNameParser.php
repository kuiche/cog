<?php

namespace Message\Cog\Templating;

use Message\Cog\ReferenceParserInterface;
use Message\Cog\HTTP\StatusException;

use Symfony\Component\Templating\TemplateReference;

class ViewNameParser extends \Symfony\Component\Templating\TemplateNameParser
{
	protected $_services;
	protected $_parser;
	protected $_fileTypes;

	/**
	 * Constructor.
	 *
	 * @param Services                 $services  The service container
	 * @param ReferenceParserInterface $parser    Reference parser class
	 * @param array                    $fileTypes Array of filetypes to support, in order of preference
	 */
	public function __construct(Services $services, ReferenceParserInterface $parser, array $fileTypes)
	{
		$this->_services  = $services;
		$this->_parser    = $parser;
		$this->_fileTypes = $fileTypes;
	}

	/**
	 * Parses a view reference & determines which view file to use.
	 *
	 * Looks at the allowed content types for the curremt request and checks,
	 * for each, if a view file exists (for each engine defined in
	 * $this->_fileTypes in order of priority). As soon as it finds a view that
	 * exists, it returns this.
	 *
	 * @param string $reference  The view reference (without the format)
	 *
	 * @return string            The view file path
	 *
	 * @throws StatusException   If the view format could not be determined
	 *
	 * @todo What if there's no request object?
	 */
	public function parse($reference)
	{
		// Get the current HTTP request
		$request = $this->_services['request'];
		// Get the base file name from the reference parser
		$baseFileName = $this->_parser->parse($reference)->getFullPath('View');

		// Loop through each content type
		foreach ($request->getAllowedContentTypes() as $mimeType) {
			$format = $request->getFormat($mimeType);

			// Loop through the engines in order of preference
			foreach ($this->_fileTypes as $engine) {
				$fileName = SYSTEM_PATH . 'library/' . $baseFileName . '.' . $format . '.' . $engine;
				// Check if a view file exists for this format and this engine
				if (file_exists($fileName)) {
					return new TemplateReference($fileName, $engine);
				}
			}
		}

		throw new StatusException(
			sprintf(
				'View format could not be determined for reference `%s`',
				$reference
			),
			StatusException::NOT_ACCEPTABLE
		);
	}
}