<?php

namespace App\Model;

class Trail
{
    /**
     * @var int;
     */
    private $id;

    /**
     * @var string;
     */
    private $nom;

    /**
     * @var string;
     */
    private $auteur;

    /**
     * @var float[];
     */
    private $position;

    /**
     * @var string;
     */
    private $photo;

    /**
     * @var \DateTime;
     */
    private $dateCreation;

    /**
     * @var \DateTime;
     */
    private $dateModification;

    /**
     * @var ?\DateTime;
     */
    private $dateSuppression;

    /**
     * @var ?Occurrence[]
     */
    private $occurrences;

    /**
     * @var ?Chemin;
     */
    private $chemin;

    /**
     * @var ?string;
     */
    private $details;
}
