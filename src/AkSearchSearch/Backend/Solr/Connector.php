<?php
/**
 * AK: Extended SOLR connector.
 *
 * PHP version 7
 *
 * Copyright (C) AK Bibliothek Wien 2019.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category AKsearch
 * @package  Search
 * @author   Michael Birkner <michael.birkner@akwien.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:search_service Wiki
 */

namespace AkSearchSearch\Backend\Solr;

use VuFindSearch\ParamBag;

/**
 * AK: Extending SOLR connector.
 *
 * @category AKsearch
 * @package  Search
 * @author   Michael Birkner <michael.birkner@akwien.at>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:architecture:search_service Wiki
 */
class Connector extends \VuFindSearch\Backend\Solr\Connector
{

    /**
     * Get unique key.
     * 
     * AK: Return unique key based on the [AkSearch]->idFields config in
     *     searches.ini. If 'id' is contained in this config it is returned. If not,
     *     the first value found in the comma separated string is returned. Fallback
     *     value is 'id'.
     *
     * @return string
     */
    public function getUniqueKey() {
    	$uKey = 'id';
    	if ($this->uniqueKey != null && !empty($this->uniqueKey)) {
	    	$idFieldsArr = preg_split('/\s*,\s*/', $this->uniqueKey);
	    	$uKey = (in_array('id', $idFieldsArr)) ? 'id' : $idFieldsArr[0];
    	}
        return $uKey;
    }

    /**
     * Return document specified by id.
     * 
     * AK: Using multiple ID fields for returning a document. The ID fields are
     *     specified in [AkSearch]->idFields config in searches.ini.
     *
     * @param string   $id     The document to retrieve from Solr
     * @param ParamBag $params Parameters
     *
     * @return string
     */
    public function retrieve($id, ParamBag $params = null)
    {
        // AK: Use query string for multiple ID searches.
        $queryString = $this->getMultipleIdQueryString($id);
        $params = $params ?: new ParamBag();
        $params->set('q', $queryString);

        $handler = $this->map->getHandler(__FUNCTION__);
        $this->map->prepare(__FUNCTION__, $params);

        return $this->query($handler, $params);
    }

    /**
     * Return records similar to a given record specified by id.
     *
     * Uses MoreLikeThis Request Component or MoreLikeThis Handler
     * 
     * AK: Using multiple ID fields for returning similar documents. The ID fields
     *     are specified in [AkSearch]->idFields config in searches.ini.
     *
     * @param string   $id     ID of given record (not currently used, but
     * retained for backward compatibility / extensibility).
     * @param ParamBag $params Parameters
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function similar($id, ParamBag $params)
    {
        // AK: Use query string for multiple ID searches.
        $queryString = $this->getMultipleIdQueryString($id);
        $params->set('q', $queryString);

        $handler = $this->map->getHandler(__FUNCTION__);
        $this->map->prepare(__FUNCTION__, $params);
        return $this->query($handler, $params);
    }

    /**
     * AK: Get a query string that searches in multiple ID fields (ORed together).
     *
     * @param string $id    The record ID to search for
     * 
     * @return string       The query string for a search in multiple ID fields
     */
    protected function getMultipleIdQueryString(string $id) {
        // AK: Use possible ID fields that are defined in [AkSearch]->idFields config
        //     in searches.ini and split them into an array.
        $idFieldsArr = preg_split('/\s*,\s*/', $this->uniqueKey);
        
        // AK: Construct a solr query string that ORs together the ID fields. This
        //     query string can be used in the search query.
    	$solrQueryStrings = [];
    	foreach ($idFieldsArr as $uKey) {
            $solrQueryStrings[] = sprintf('%s:"%s"', $uKey, addcslashes($id, '"'));
    	}
        $solrQueryString = implode(' || ', $solrQueryStrings);
        
        return $solrQueryString;
    }
}
