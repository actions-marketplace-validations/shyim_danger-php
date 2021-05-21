<?php
declare(strict_types=1);

namespace Danger\Component\Struct;

abstract class PullRequest
{
    public string $id;

    public string $title;

    public string $body;

    public \DateTime $createdAt;

    public \DateTime $updatedAt;

    /**
     * @var string[]
     */
    public array $labels = [];

    public int $additionsAmount;

    public int $deletionsAmount;

    public int $changedFilesAmount;

    public array $assignees;

    abstract public function getCommits(): CommitCollection;

    abstract public function getFiles(): FileCollection;
}
