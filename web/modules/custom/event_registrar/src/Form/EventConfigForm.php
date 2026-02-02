<?php

declare(strict_types=1);

namespace Drupal\event_registrar\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class EventConfigForm extends FormBase {

  private Connection $database;

  public function __construct(Connection $database, MessengerInterface $messenger) {
    $this->database = $database;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  public function getFormId(): string {
    return 'event_registrar_event_config_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['reg_start_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration start date'),
      '#required' => TRUE,
    ];

    $form['reg_end_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Registration end date'),
      '#required' => TRUE,
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
    ];

    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the event'),
      '#required' => TRUE,
      '#options' => [
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Conference' => $this->t('Conference'),
        'One-day Workshop' => $this->t('One-day Workshop'),
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->database->insert('event_configurations')
      ->fields([
        'reg_start_date' => (string) $form_state->getValue('reg_start_date'),
        'reg_end_date' => (string) $form_state->getValue('reg_end_date'),
        'event_date' => (string) $form_state->getValue('event_date'),
        'event_name' => (string) $form_state->getValue('event_name'),
        'category' => (string) $form_state->getValue('category'),
      ])
      ->execute();

    $this->messenger->addStatus($this->t('Event configuration saved.'));
  }

}
