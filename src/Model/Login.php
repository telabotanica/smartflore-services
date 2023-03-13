<?php

namespace App\Model;

use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Groups;

class Login
{
	/**
	 * @var string
	 * @OA\Property(
	 *     type="string",
	 *     example="login@example.org"
	 * )
	 * @Groups({"Login"})
	 */
	private $login;
	
	/**
	 * @var string
	 * @OA\Property(
	 *     type="string",
	 *     example="Pa$$W0rd!"
	 * )
	 * @Groups({"Login"})
	 */
	private $password;
	
	/**
	 * @return string
	 */
	public function getLogin(): string
	{
		return $this->login;
	}
	
	/**
	 * @param string $login
	 */
	public function setLogin(string $login): void
	{
		$this->login = $login;
	}
	
	/**
	 * @return string
	 */
	public function getPassword(): string
	{
		return $this->password;
	}
	
	/**
	 * @param string $password
	 */
	public function setPassword(string $password): void
	{
		$this->password = $password;
	}
	
	
}
