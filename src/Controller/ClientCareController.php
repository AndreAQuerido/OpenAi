<?php

namespace App\Controller;

use OpenAI;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ClientCareController extends AbstractController
{
    protected $MAX_TOKEN = 100;


    #[Route('/prompt', methods: ['POST'])]
    public function prompt(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);
        $prompt = $params["prompt"] ?? null;

        $client = OpenAI::client($_ENV["API_KEY"]);
        if ($prompt) {
            $result = $this->waitForOpenAiResponse($client, $prompt);
            $string = trim(preg_replace('/\s\s+/', ' ', $result['choices'][0]['text']));
            return new JsonResponse(["prompt" => $prompt, "response"=> $string]);
        }
        return new Response("No prompt",status: 400);
    }

    private function waitForOpenAiResponse($openaiClient, $text, $timeout = 30)
    {

        $response = $openaiClient->completions()->create([
            'model' => $_ENV["MODEL"],
            'prompt' => $text,
            'max_tokens' => $this->MAX_TOKEN,
            'temperature' => 0,
            ]);


        return $response;
    }
}
