<?php
/**
 * This file is part of DoctrineRestDriver.
 *
 * DoctrineRestDriver is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DoctrineRestDriver is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with DoctrineRestDriver.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Circle\DoctrineRestDriver\Types;

use Circle\DoctrineRestDriver\Enums\HttpMethods;
use Circle\DoctrineRestDriver\MetaData;
use PHPSQLParser\PHPSQLParser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps the response content of any query to a valid
 * Doctrine result
 *
 * @author    Tobias Hauck <tobias@circle.ai>
 * @copyright 2015 TeeAge-Beatz UG
 */
class Result {

    /**
     * @var array
     */
    private $result;

    /**
     * @var mixed
     */
    private $id;

    /**
     * Result constructor
     *
     * @param string   $query
     * @param Response $response
     * @param array    $options
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function __construct($query, $requestMethod, Response $response, array $options = []) {
        $tokens  = (new PHPSQLParser())->parse($query);
        $content = Format::create($options)->decode($response->getContent());
        $responseCode = $response->getStatusCode();

        $this->result = $this->createResult($tokens, $requestMethod, $responseCode, $content);
        $this->id     = $this->createId($tokens);
    }

    /**
     * Returns a valid Doctrine result
     *
     * @return array
     */
    public function get() {
        return $this->result;
    }

    /**
     * returns the id of the result
     */
    public function id() {
        return $this->id;
    }

    /**
     * returns the id
     *
     * @param  array $tokens
     * @return mixed
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    private function createId(array $tokens) {
        $idColumn = Identifier::column($tokens, new MetaData());
        return empty($this->result[$idColumn]) ? null : $this->result[$idColumn];
    }

    /**
     * returns the result
     *
     * @param  array      $tokens
     * @param  array|null $content
     * @return array
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    private function createResult(array $tokens, $requestMethod, $responseCode, array $content = null) {
        if($responseCode >= 400 && $responseCode < 600) return [];
        if ($requestMethod === HttpMethods::DELETE) return [];
        $result = $requestMethod === HttpMethods::GET ? SelectResult::create($tokens, $content) : $content;
        krsort($result);

        return $result;
    }
}