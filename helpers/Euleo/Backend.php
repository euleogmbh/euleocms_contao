<?php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

interface Euleo_Backend
{
	/**
	 * Constructor
	 * @param Controller $module
	 */
	public function __construct(Controller $module, Euleo_Contao $bridge);
	
	/**
	 * Returns all the rows by means of DCA
	 * @param string $table
	 * @param int $id
	 */
	public function getRows($table, $id);
	
	/**
	 * Returns the supported languages by page (root-level page must exist for each language)
	 */
	public function getLanguages();
	
	/**
	 * Fetches the translated rows from Euleo and creates/updates the translation
	 * @param array $rows
	 */
	public function callback( $rows );
}