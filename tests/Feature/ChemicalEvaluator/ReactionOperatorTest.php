<?php

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\ReactionOperator;

describe('ReactionOperator', function () {
    test('will create a reaction operator with correct reaction type', function ($targetReaction, $expectedReactionType) {
        $target = new ReactionOperator($targetReaction);

        expect($target->type)->toBe($expectedReactionType);
    })->with([
        'net forward reaction' => ['->', Reactions::NET_FORWARD_REACTION],
        'stoichiometric reaction' => ['=', Reactions::STOICHIOMETRIC_REACTION],
    ]);
});
