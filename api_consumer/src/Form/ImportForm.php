<?php

namespace Drupal\api_consumer\Form;

use Drupal\Core\Form\FormBase;
use Drupal\api_consumer\PokemonService;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DefaultFm.
 */
class ImportForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return '*';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Pokemons'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $pokemons_first_call = PokemonService::BeginEntry();
      // Case of not getting answer from the server
    if ($pokemons_first_call != FALSE) {
      $operations = [];

      foreach ($pokemons_first_call as $pokemons) {
        $operations[] =  [
          '\Drupal\api_consumer\PokemonService::createPokemons',
          [$pokemons]
        ];
      }

      $batch = array(
        'title' => t('Creating Pokemons...'),
        'operations' => $operations,
        'finished' => '\Drupal\api_consumer\PokemonService::createPokemonsFinishedCallback',
      );

      batch_set($batch);
    } else {
      drupal_set_message(t("An Error Occured Please retry Again"), "error");
    }
  }
}
