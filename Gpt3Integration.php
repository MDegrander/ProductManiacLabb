<?php
// GPT3Integration.php

// Inkludera Composer autoloader
require_once 'vendor/autoload.php';

class Gpt3Integration {
    // Deklarera en privat variabel för API-nyckeln
    private $api_key;

    // Konstruktor
    public function __construct() {
        $this->api_key = get_option('openai_api_key', '');
    }

    // Funktion för att analysera text med OpenAI API
    public function analyzeText($text) {
        // Här kan du lägga till kod för att anropa OpenAI API
        // och returnera analyserade data
    }

    // Funktion för att omskriva text med OpenAI API
    public function rewriteText($text) {
        // Här kan du lägga till kod för att anropa OpenAI API
        // och returnera den omskrivna texten
    }
    //Test OpenAI settings
public function testConnection() {
    try {
        $openai = new \OpenAIAPI(array(
            "api_key" => $this->api_key
        ));

        $response = $openai->listEngines();
        if ($response) {
            return "Anslutning till OpenAI lyckades.";
        } else {
            return "Kunde inte ansluta till OpenAI.";
        }
    } catch (Exception $e) {
        error_log("Ett fel inträffade vid anslutning till OpenAI: " . $e->getMessage());
        return "Ett fel inträffade: " . $e->getMessage();
    }
}
}

