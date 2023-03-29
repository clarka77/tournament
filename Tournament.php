<?php

/*
 * By adding type hints and enabling strict type checking, code can become
 * easier to read, self-documenting and reduce the number of potential bugs.
 * By default, type declarations are non-strict, which means they will attempt
 * to change the original type to match the type specified by the
 * type-declaration.
 *
 * In other words, if you pass a string to a function requiring a float,
 * it will attempt to convert the string value to a float.
 *
 * To enable strict mode, a single declare directive must be placed at the top
 * of the file.
 * This means that the strictness of typing is configured on a per-file basis.
 * This directive not only affects the type declarations of parameters, but also
 * a function's return type.
 *
 * For more info review the Concept on strict type checking in the PHP track
 * <link>.
 *
 * To disable strict typing, comment out the directive below.
 */

declare(strict_types=1);

class Tournament {
    private ScoreParser $parser;

    public function __construct() {
        $this->parser = new ScoreParser();
    }

    public function tally(string $scores): string {
        $teams = $this->parser->parse($scores);
        $board = new Board($teams);
        return (string)$board;
    }
}
class ScoreParser {
    public function parse(string $scores): array {
        if (!$scores)
            return [];

        $teams = [];
        $matches = explode("\n", $scores);

        foreach ($matches as $match) {
            [$name1, $name2, $outcome] = explode(';', $match);
            $teams[$name1] ??= new Team($name1);
            $teams[$name2] ??= new Team($name2);
            $this->match($teams[$name1], $teams[$name2], $outcome);
        }

        $this->sort($teams);
        return $teams;
    }

    private function match(Team $team1, Team $team2, string $outcome) {
        match ($outcome) {
            'win' => $team1->beat($team2),
            'loss' => $team2->beat($team1),
            'draw' => $team2->tied($team1),
        };
    }

    private function sort(array &$teams) {
        usort($teams, fn(Team $a, Team $b) => $a->score() === $b->score() ? $a->name() <=> $b->name() : $b->score() <=> $a->score());
    }
}

class Board {
    private array $teams;

    public function __construct(array $teams) {
        $this->teams = $teams;
    }

    public function __toString() {
        $output = [$this->headerRow()];

        foreach ($this->teams as $team) {
            $stats = $this->statsFor($team);
            $output[] = $this->rowFor($stats);
        }

        return implode("\n", $output);
    }

    private function headerRow(): string {
        return $this->rowFor(['Team', 'MP', 'W', 'D', 'L', 'P']);
    }

    private function rowFor(array $rowData): string {
        $name = str_pad(array_shift($rowData), 30);
        $rowData = array_map(fn($col) => str_pad((string)$col, 2, ' ', STR_PAD_LEFT), $rowData);

        return implode(' | ', [$name, ...$rowData]);
    }

    private function statsFor(Team $team): array {
        return [
            $team->name(),
            $team->matches(),
            $team->wins(),
            $team->draws(),
            $team->losses(),
            $team->score(),
        ];
    }
}

class Team {
    private string $name;
    private int $wins = 0;
    private int $draws = 0;
    private int $losses = 0;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function beat(Team $other) {
        $this->wins += 1;
        $other->losses += 1;
    }

    public function tied(Team $other) {
        $this->draws += 1;
        $other->draws += 1;
    }

    public function matches(): int {
        return $this->wins + $this->draws + $this->losses;
    }

    public function score(): int {
        return $this->wins * 3 + $this->draws;
    }

    public function name(): string {
        return $this->name;
    }

    public function wins(): int {
        return $this->wins;
    }

    public function draws(): int {
        return $this->draws;
    }

    public function losses(): int {
        return $this->losses;
    }
}
