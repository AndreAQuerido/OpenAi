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
    protected static $MAX_TOKEN = 200;

    protected static $AGENT_DESCRIPTION = "I want you to act as a Client Care Specialist from ExoClick, a digital marketing company that provides online advertising services to both advertisers and publishers all over the world. You will receive questions from Publishers regarding Fluid Player, and I want you to provide responses to client inquiries in a formal but friendly manner, adapting your communication style to the client's style and showing empathy at all times. Your replies should be clear and concise, while also avoiding the use of too-technical terminology and always matching the client's language.";

    protected static $PROMPT_CLIENT_PREFIX = "Client";

    protected static $PROMPT_MESSAGE_SEPARATOR = "\n###\n";

    protected static $PROMPT_AGENT_PREFIX = "Agent";

    //Model 0 = chat (current)
    //Model 1 = translate
    //Model 3 = categorize emails
    protected static $SUPPORTED_MODELS = ["ada:ft-exogroup-2023-05-19-09-46-59", "davinci:ft-exogroup-2023-05-18-17-52-05", "davinci:ft-exogroup:lol-troll-2023-05-17-14-27-42"];

    #[Route('/prompt', methods: ['POST'])]
    public function prompt(Request $request): Response
    {
        $params = json_decode($request->getContent(), true);
        $prompt = $params["prompt"] ?? null;
        $model = isset($params["model"]) && is_numeric($params["model"]) ? $params["model"] : 0;

        $client = OpenAI::client($_ENV["API_KEY"]);

        if ($prompt) {
            $result = $this->waitForOpenAiResponse($client, $prompt, $model);
            if (
                isset($result['choices'][0]['text']) && isset($result["choices"])
            ){
                $string = trim(preg_replace('/\s\s+/', ' ', $result['choices'][0]['text']));
                $string = preg_replace('/[^a-zA-Z0-9_ %\[\]\.\(\)%&-]/s', '', $string);
                return new JsonResponse(["prompt" => $prompt, "response"=> $string]);
            }
            else {
                return new Response("Something went wrong",status: 500);
            }
        }
        return new Response("No prompt",status: 400);
    }

    private function waitForOpenAiResponse($openaiClient, $text, $model = 0, $timeout = 30)
    {
        $start_time = time();
        $response = null;

        while (time() - $start_time < $timeout) {
        $response = $openaiClient->completions()->create([
            'model' => self::$SUPPORTED_MODELS[$model],
            'prompt' =>
                self::$AGENT_DESCRIPTION .
                self::$PROMPT_CLIENT_PREFIX.": ".$text. self::$PROMPT_MESSAGE_SEPARATOR . self::$PROMPT_AGENT_PREFIX.":",
            'max_tokens' => self::$MAX_TOKEN,
            'temperature' => 0,
            "stop"=> ["END"]
            ]);
            if (
                isset($response['choices'][0]['text']) && isset($response["choices"])
            ) {
                break;
            }

            sleep(1);
        }


        return $response;
    }
}
