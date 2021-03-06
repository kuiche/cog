<?php

namespace Message\Cog\Field;

interface ContentTypeInterface
{
	/**
	 * Get the identifying name of this content type.
	 *
	 * These must be unique: if there is more than one content type registered with
	 * the same name, an error will be thrown.
	 *
	 * @return string The content type name
	 */
	public function getName();

	/**
	 * Get a nicely formatted name for this content that can be displayed to
	 * the user.
	 *
	 * @return string The content type name
	 */
	public function getDisplayName();

	/**
	 * Get a description for this content.
	 *
	 * @return string The content type description
	 */
	public function getDescription();

	/**
	 * Set the fields & groups for this content on a field factory instance.
	 *
	 * @param  Factory $factory The field factory to use
	 */
	public function setFields(Factory $factory);
}