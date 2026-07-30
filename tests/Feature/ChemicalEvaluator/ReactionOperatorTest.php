<?php

use App\ChemicalEvaluator\Enums\Reactions;
use App\ChemicalEvaluator\Evaluator;
use App\ChemicalEvaluator\NoReactionOperand;
use App\ChemicalEvaluator\ReactionMixture;
use App\ChemicalEvaluator\ReactionOperator;
use App\ChemicalEvaluator\Reactant;
use App\ChemicalEvaluator\Tokenizer;

describe('ReactionOperator', function () {
    test('will create a reaction operator with correct reaction type', function ($targetReaction, $expectedReactionType) {
        $target = new ReactionOperator($targetReaction);

        expect($target->type)->toBe($expectedReactionType);
    })->with([
        'net forward reaction' => ['->', Reactions::NET_FORWARD_REACTION],
        'stoichiometric reaction' => ['=', Reactions::STOICHIOMETRIC_REACTION],
    ]);

    test('returns No Reaction when lone element has lower activity rank than compound element', function () {
        Cache::flush();

        // Cu (rank=5) cannot displace Fe (rank=20) from FeCl2
        $loneElement = new Reactant('Cu');
        $compound = new Reactant('FeCl<sub>2</sub>');
        $mixture = new ReactionMixture($loneElement, $compound);

        $operator = new ReactionOperator('->');
        $result = $operator->operate($mixture);

        expect($result)->toBeInstanceOf(NoReactionOperand::class)
            ->and((string) $result)->toBe('No Reaction');
    });

    test('proceeds when lone element has higher activity rank than compound element', function () {
        Cache::flush();

        // Fe (rank=20) can displace Cu (rank=5) from CuCl2
        $loneElement = new Reactant('Fe');
        $compound = new Reactant('CuCl<sub>2</sub>');
        $mixture = new ReactionMixture($loneElement, $compound);

        $operator = new ReactionOperator('->');
        $result = $operator->operate($mixture);

        expect($result)->not->toBeInstanceOf(NoReactionOperand::class);
    });

    test('evaluator returns No Reaction for Cu + FeCl2', function () {
        Cache::flush();

        $tokenizer = new Tokenizer();
        $tokenizer->tokenize('Cu + FeCl<sub>2</sub> ->');
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->toBe('No Reaction');
    });

    test('evaluator proceeds for Fe + CuCl2 (returns compound placeholder until Step 8)', function () {
        Cache::flush();

        $tokenizer = new Tokenizer();
        $tokenizer->tokenize('Fe + CuCl<sub>2</sub> ->');
        $tokenizer->organize();

        $evaluator = new Evaluator($tokenizer);
        $result = $evaluator->evaluate();

        expect($result)->not->toBe('No Reaction');
    });
});
