<?php
/**
 * @file
 * This file provides some methods for doing RDF queries.
 *
 * The essance of this file was taken from some commits that Adam Vessy made to
 * Islandora 6.x, so I'd like to give him some credit here.
 */

class RepositoryQuery {

  public $connection;

  /**
   * Construct a new RI object.
   *
   * @param RepositoryConnection $connection
   *   The connection to connect to the RI with.
   */
  public function __construct(RepositoryConnection $connection) {
    $this->connection = $connection;
  }

  /**
   * Parse the passed in Sparql XML string into a more easily usable format.
   *
   * @param string $sparql
   *   A string containing Sparql result XML.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  public static function parseSparqlResults($sparql) {
    // Load the results into a SimpleXMLElement.
    $doc = new SimpleXMLElement($sparql, 0, FALSE, 'http://www.w3.org/2001/sw/DataAccess/rf1/result');

    // Storage.
    $results = array();
    // Build the results.
    foreach ($doc->results->children() as $result) {
      // Built a single result.
      $r = array();
      foreach ($result->children() as $element) {
        $val = array();

        $attrs = $element->attributes();
        if (!empty($attrs['uri'])) {
          $val['value'] = self::pidUriToBarePid((string) $attrs['uri']);
          $val['uri'] = (string) $attrs['uri'];
          $val['type'] = 'pid';
        }
        else {
          $val['type'] = 'literal';
          $val['value'] = (string) $element;
        }

        // Map the name to the value in the array.
        $r[$element->getName()] = $val;
      }

      // Add the single result to the set to return.
      $results[] = $r;
    }
    return $results;
  }

  /**
   * Performs the given Resource Index query and return the results.
   *
   * @param string $query
   *   A string containing the RI query to perform.
   * @param string $type
   *   The type of query to perform, as used by the risearch interface.
   * @param int $limit
   *   An integer, used to limit the number of results to return.
   * @param string $format
   *   A string indicating the type format desired, as supported by the
   *   underlying triple store.
   *
   * @return string
   *   The contents returned, in the $format specified.
   */
  protected function internalQuery($query, $type = 'itql', $limit = -1, $format = 'Sparql') {
    // Construct the query URL.
    $url = '/risearch';
    $seperator = '?';

    $this->connection->addParam($url, $seperator, 'type', 'tuples');
    $this->connection->addParam($url, $seperator, 'flush', TRUE);
    $this->connection->addParam($url, $seperator, 'format', $format);
    $this->connection->addParam($url, $seperator, 'lang', $type);
    $this->connection->addParam($url, $seperator, 'query', $query);

    // Add limit if provided.
    if ($limit > 0) {
      $this->connection->addParam($url, $seperator, 'limit', $limit);
    }

    $result = $this->connection->getRequest($url);

    return $result['content'];
  }

  /**
   * Performs the given Resource Index query and return the results.
   *
   * @param string $query
   *   A string containing the RI query to perform.
   * @param string $type
   *   The type of query to perform, as used by the risearch interface.
   * @param int $limit
   *   An integer, used to limit the number of results to return.
   *
   * @return array
   *   Indexed (numerical) array, containing a number of associative arrays,
   *   with keys being the same as the variable names in the query.
   *   URIs beginning with 'info:fedora/' will have this beginning stripped
   *   off, to facilitate their use as PIDs.
   */
  public function query($query, $type = 'itql', $limit = -1) {
    // Pass the query's results off to a decent parser.
    return self::parseSparqlResults($this->internalQuery($query, $type, $limit));
  }

  /**
   * Thin wrapper for self::_performRiQuery().
   *
   * @see self::performRiQuery()
   */
  public function itqlQuery($query, $limit = -1) {
    return $this->query($query, 'itql', $limit);
  }

  /**
   * Thin wrapper for self::performRiQuery().
   *
   * @see self::_performRiQuery()
   */
  public function sparqlQuery($query, $limit = -1, $offset = 0) {
    return $this->query($query, 'sparql', $limit, $offset);
  }

  /**
   * Utility function used in self::performRiQuery().
   *
   * Strips off the 'info:fedora/' prefix from the passed in string.
   *
   * @param string $uri
   *   A string containing a URI.
   *
   * @return string
   *   The input string less the 'info:fedora/' prefix (if it has it).
   *   The original string otherwise.
   */
  protected static function pidUriToBarePid($uri) {
    $chunk = 'info:fedora/';
    $pos = strpos($uri, $chunk);
    // Remove info:fedora/ chunk.
    if ($pos === 0) {
      return substr($uri, strlen($chunk));
    }
    // Doesn't start with info:fedora/ chunk...
    else {
      return $uri;
    }
  }

  /**
   * Get the count of tuples a query selects.
   *
   * Given that some langauges do not have a built-in construct for performing
   * counting/aggregation, a method to help with this is desirable.
   *
   * @param string $query
   *   A query for which to count the number tuples returned.
   *
   * @return int
   *   The number of tuples which were selected.
   */
  public function countQuery($query, $type='itql') {
    $content = $this->internalQuery($query, $type, -1, 'count');
    return intval($content);
  }
}
