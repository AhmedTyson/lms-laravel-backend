<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TypeSafeJevService
{
    protected string $apiKey;

    protected string $baseUrl;

    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.typesafe.api_key', '');
        $this->baseUrl = config('services.typesafe.base_url', 'https://api.typesafe.ai/v1/systemone');
        $this->model = config('services.typesafe.model', 'jev-latest');
    }

    /**
     * Evaluate state against a map of questions.
     *
     * @param  string|array<mixed>  $state
     * @param  array<string, array<string, mixed>>  $questions
     * @return array<string, mixed>
     */
    public function evaluate(mixed $state, array $questions): array
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('TypeSafe API Key (TYPESAFE_API_KEY) is not configured.');
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->post($this->baseUrl, [
                'state' => $state,
                'model' => $this->model,
                'questions' => $questions,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('TypeSafe API Evaluation failed: '.$response->body());
        }

        /** @var array<string, mixed> */
        return $response->json();
    }

    /**
     * Convenience method for a Noul (Yes/No) probability question.
     *
     * @param  array{true?: string, false?: string}|null  $criteria
     */
    public function noul(mixed $state, string $instructions, ?array $criteria = null): float
    {
        $question = [
            'type' => 'noul',
            'instructions' => $instructions,
        ];

        if ($criteria !== null) {
            $question['criteria'] = $criteria;
        }

        $res = $this->evaluate($state, ['q' => $question]);

        return (float) ($res['answers']['q']['noul'] ?? 0.0);
    }

    /**
     * Convenience method for a Choice decision question.
     *
     * @param  array<string, string|null>  $criteria
     * @return array{choice: string, confidence: float, probabilities: array<string, float>}
     */
    public function choice(mixed $state, string $instructions, array $criteria): array
    {
        $question = [
            'type' => 'choice',
            'instructions' => $instructions,
            'criteria' => $criteria,
        ];

        $res = $this->evaluate($state, ['q' => $question]);
        $answer = $res['answers']['q'] ?? [];

        return [
            'choice' => (string) ($answer['choice'] ?? ''),
            'confidence' => (float) ($answer['confidence'] ?? 0.0),
            'probabilities' => (array) ($answer['probabilities'] ?? []),
        ];
    }
}
