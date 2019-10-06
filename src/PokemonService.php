<?php

namespace Drupal\api_consumer;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Class PokemonService.
 */
class PokemonService {


  /**
   * Node type
   */
  const NODE_TYPE = "pokemon";


  /**
   * No need to make more calls to catch the next url from the API
   */
  const POKEURL_FIRST_GENERATION = 'https://pokeapi.co/api/v2/pokemon?limit=1000';


  /**
   * This is the function called to retrieve all pokemon  Urls
   *
   * @return bool|array
   */
  public static function BeginEntry() {
    try {
      $result = [];
      $next_url = self::POKEURL_FIRST_GENERATION;
      // In Case of You wan't to decrease the limit param
      do {
        $api_response = file_get_contents($next_url);
        $json_response = json_decode($api_response, TRUE);
        $next_url = $json_response["next"];
        $result = array_merge($result, $json_response["results"]);
      } while (isset($json_response["next"]));

      return ($result) ?: FALSE;
    } catch (Exception $e) {
      $e->printMessage();
    }
  }

  /**
   * @param $pokemons
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createPokemonsFromCron($pokemons) {
    foreach ($pokemons as $pokemon) {
      $pokemon_name = self::createPokemon($pokemon);
    }
  }

  /**
   * For creating pokemons & some batch proccessing
   *
   * @param $results
   * @param $context
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createPokemons($results, &$context) {
    $pokemon_name = self::createPokemon($results);
    $context['message'] = sprintf(
      "Creating %s",
      $pokemon_name
    );
    $context['results'][] = sizeof($results);
  }


  /**
   * create & get data from server for a pokemon & save the entity
   *
   * @param $result
   *
   * @return bool|mixed
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function createPokemon($result) {
    $pokemon_name = $result['name'];
    $pokemon_url = $result['url'];
    if (isset($pokemon_url) && !empty($pokemon_url)) {
      $api_response = file_get_contents($pokemon_url);
      $json_response = json_decode($api_response, TRUE);
      $pokemon = self::getPokemonByName($pokemon_name);
      $pokemon = self::updatePokemon($pokemon, $json_response);

      try {
        $pokemon->save();
        return $pokemon_name;
      } catch (Exception $e) {
        return FALSE;
      }
    }
  }

  /**
   * Get a pokemon by name to avoid duplicated element
   *
   * @param $name
   *
   * @return mixed
   */
  protected static function getPokemonByName($name) {
    $query = \Drupal::entityQuery('node')
      ->condition('title', $name)
      ->condition('type', self::NODE_TYPE);
    $node = $query->execute();

    if (isset($node) && !empty($node)) {
      $node = Node::load(reset($node));
    }
    else {
      $node = Node::create([
        'type' => self::NODE_TYPE,
        'title' => $name,
      ]);
    }

    return $node;
  }


  /**
   * Pokemon mapping & setting object
   *
   * @param $pokemon
   * @param $response
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected static function updatePokemon($pokemon, $response) {
    $abilities = self::fetchDataFromArrays($response["abilities"], "ability");
    $moves = self::fetchDataFromArrays($response["moves"], "move");
    $types = self::fetchDataFromArrays($response["types"], "type");
    $base_exp = $response["base_experience"];
    $game_index = $response["game_indices"][0]["game_index"];
    $image_url = $response["sprites"]["front_shiny"];
    $weight = $response["weight"];
    $height = $response["height"];
    $pokemon = self::getStats($response["stats"], $pokemon);
    $pokemon = self::setGeneration($game_index, $pokemon);
    $pokemon->set("field_pokemon_abilities", $abilities);
    $pokemon->set("field_pokemon_game_index", $game_index);
    $pokemon->set("field_pokemon_img_url", $image_url);
    $pokemon->set("field_pokemon_moves", $moves);
    $pokemon->set("field_pokemon_height", $height);
    $pokemon->set("field_pokemon_type", $types);
    $pokemon->set("field_pokemon_base_exp", $base_exp);
    if ($pokemon->get("field_pokemon_picture")->isEmpty()) {
      $uri = (isset($image_url) && !empty($image_url)) ? system_retrieve_file($image_url) : system_retrieve_file("https://via.placeholder.com/128.png");

      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $uri]);
      $file = reset($files);

      if (!$file) {
        $file = File::create([
          'uri' => $uri,
        ]);
        $file->save();
      }
      $pokemon->set("field_pokemon_picture", [
        'target_id' => $file->id(),
        'alt' => 'https://via.placeholder.com/128.png',
        'title' => 'Lorem',
      ]);
    }
    return $pokemon;
  }


  /**
   * Some logic to categorize by generation  avoiding  more calls to server
   * (HACK)
   *
   *
   * @param $game_index
   * @param $pokemon
   *
   * @return mixed
   */
  protected static function setGeneration($game_index, $pokemon) {
    if (empty($game_index)) {
      $pokemon->set("field_pokemon_generation", 100);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 151) {
      $pokemon->set("field_pokemon_generation", 1);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 251) {
      $pokemon->set("field_pokemon_generation", 2);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 386) {
      $pokemon->set("field_pokemon_generation", 3);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 493) {
      $pokemon->set("field_pokemon_generation", 4);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 649) {
      $pokemon->set("field_pokemon_generation", 5);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 721) {
      $pokemon->set("field_pokemon_generation", 6);
      return $pokemon;
    }

    if (isset($game_index) && $game_index <= 807) {
      $pokemon->set("field_pokemon_generation", 7);
      return $pokemon;
    }

    $pokemon->set("field_pokemon_generation", 8);
    return $pokemon;
  }

  /**
   *
   *To fetch stats from response
   *
   * @param $states
   * @param $pokemon
   *
   * @return mixed
   */
  protected static function getStats($states, $pokemon) {
    foreach ($states as $item) {
      $base_exp = $item["base_stat"];
      switch ($item["stat"]["name"]) {
        case "speed":
          $pokemon->set("field_pokemon_speed", $base_exp);
          break;
        case "special-defense":
          $pokemon->set("field_special_defense", $base_exp);
          break;
        case "special-attack":
          $pokemon->set("field_speacial_attack", $base_exp);
          break;
        case "defense":
          $pokemon->set("field_defense", $base_exp);
          break;
        case "attack":
          $pokemon->set("field_pokemon_attack", $base_exp);
          break;
        case "hp":
          $pokemon->set("field_pokemon_hp", $base_exp);
          break;
      }
    }
    return $pokemon;
  }


  /**
   * Extract Data method
   *
   * @param $arrays
   * @param $label
   *
   * @return array
   */
  protected static function fetchDataFromArrays($arrays, $label) {
    $result = [];
    foreach ($arrays as $key => $array) {
      $result[]["value"] = $array[$label]['name'];
    }
    return $result;
  }


  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  public function createPokemonsFinishedCallback($success, $results, $operations) {

    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One pokemon imported.',
        '@count pokemon imported.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message, 'error');
  }
}
