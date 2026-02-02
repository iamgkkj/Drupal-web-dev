<?php

declare(strict_types=1);

namespace Drupal\event_registrar\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Time\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class EventRegistrationForm extends FormBase {

  private Connection $database;

  private RequestStack $requestStack;

  private MessengerInterface $messenger;

  private TimeInterface $time;

  public function __construct(Connection $database, RequestStack $request_stack, MessengerInterface $messenger, TimeInterface $time) {
    $this->database = $database;
    $this->requestStack = $request_stack;
    $this->messenger = $messenger;
    $this->time = $time;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('datetime.time')
    );
  }

  public function getFormId(): string {
    return 'event_registrar_event_registration_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!$this->hasOpenEvents()) {
      return [
        'message' => [
          '#markup' => $this->t('Event registrations are not open right now.'),
        ],
      ];
    }

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
    ];

    $selected_category = (string) $form_state->getValue('category');
    $selected_event_date = (string) $form_state->getValue('event_date');

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category of the event'),
      '#required' => TRUE,
      '#options' => $this->getCategoryOptions(),
      '#ajax' => [
        'callback' => '::updateEventDate',
        'wrapper' => 'event-date-wrapper',
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $selected_category ?: NULL,
    ];

    $form['event_date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-date-wrapper'],
    ];

    $form['event_date_wrapper']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#options' => $selected_category ? $this->getEventDateOptions($selected_category) : [],
      '#ajax' => [
        'callback' => '::updateEventName',
        'wrapper' => 'event-name-wrapper',
      ],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $selected_event_date ?: NULL,
    ];

    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $form['event_name_wrapper']['event_name_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#options' => ($selected_category && $selected_event_date) ? $this->getEventNameOptions($selected_category, $selected_event_date) : [],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $form_state->getValue('event_name_id') ?: NULL,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    return $form;
  }

  public function updateEventDate(array &$form, FormStateInterface $form_state): array {
    $form_state->setValue('event_date', NULL);
    $form_state->setValue('event_name_id', NULL);
    return $form['event_date_wrapper'];
  }

  public function updateEventName(array &$form, FormStateInterface $form_state): array {
    $form_state->setValue('event_name_id', NULL);
    return $form['event_name_wrapper'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $text_fields = ['full_name', 'college_name', 'department'];
    foreach ($text_fields as $field) {
      $value = (string) $form_state->getValue($field);
      if ($value !== '' && preg_match('/[^a-zA-Z0-9\s]/', $value)) {
        $form_state->setErrorByName($field, $this->t('Special characters are not allowed in %field.', ['%field' => $form[$field]['#title']]));
      }
    }

    $email = (string) $form_state->getValue('email');
    $event_date = (string) $form_state->getValue('event_date');

    if ($email !== '' && $event_date !== '') {
      $existing_id = $this->database->select('event_registrations', 'r')
        ->fields('r', ['id'])
        ->condition('r.email', $email)
        ->condition('r.event_date', $event_date)
        ->range(0, 1)
        ->execute()
        ->fetchField();

      if ($existing_id) {
        $form_state->setErrorByName('email', $this->t('You have already registered for this event date using this email.'));
      }
    }

    $event_name_id = $form_state->getValue('event_name_id');
    if ($event_name_id) {
      $event = $this->database->select('event_configurations', 'c')
        ->fields('c', ['reg_start_date', 'reg_end_date'])
        ->condition('c.id', (int) $event_name_id)
        ->range(0, 1)
        ->execute()
        ->fetchAssoc();

      if ($event) {
        $now = $this->time->getCurrentTime();
        $start_ts = strtotime((string) $event['reg_start_date'] . ' 00:00:00');
        $end_ts = strtotime((string) $event['reg_end_date'] . ' 23:59:59');

        if ($start_ts && $end_ts) {
          if ($now < $start_ts || $now > $end_ts) {
            $form_state->setErrorByName('event_name_id', $this->t('Registrations for this event are not open right now.'));
          }
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $event_name_id = (int) $form_state->getValue('event_name_id');

    $event = $this->database->select('event_configurations', 'c')
      ->fields('c', ['event_name', 'category', 'event_date'])
      ->condition('c.id', $event_name_id)
      ->range(0, 1)
      ->execute()
      ->fetchAssoc();

    if (!$event) {
      $this->messenger->addError($this->t('Selected event is not valid.'));
      return;
    }

    $this->database->insert('event_registrations')
      ->fields([
        'full_name' => (string) $form_state->getValue('full_name'),
        'email' => (string) $form_state->getValue('email'),
        'college_name' => (string) $form_state->getValue('college_name'),
        'department' => (string) $form_state->getValue('department'),
        'category' => (string) $event['category'],
        'event_date' => (string) $event['event_date'],
        'event_name_id' => $event_name_id,
        'created' => $this->time->getCurrentTime(),
      ])
      ->execute();

    $this->messenger->addStatus($this->t('Registration submitted successfully.'));
  }

  private function getCategoryOptions(): array {
    $today = date('Y-m-d', $this->time->getCurrentTime());
    $result = $this->database->select('event_configurations', 'c')
      ->fields('c', ['category'])
      ->condition('c.reg_start_date', $today, '<=')
      ->condition('c.reg_end_date', $today, '>=')
      ->distinct()
      ->orderBy('category', 'ASC')
      ->execute();

    $options = [];
    foreach ($result as $row) {
      $options[(string) $row->category] = (string) $row->category;
    }

    return $options;
  }

  private function getEventDateOptions(string $category): array {
    $today = date('Y-m-d', $this->time->getCurrentTime());
    $query = $this->database->select('event_configurations', 'c')
      ->fields('c', ['event_date'])
      ->condition('c.category', $category)
      ->condition('c.reg_start_date', $today, '<=')
      ->condition('c.reg_end_date', $today, '>=')
      ->distinct()
      ->orderBy('event_date', 'ASC');

    $result = $query->execute();

    $options = [];
    foreach ($result as $row) {
      $date = (string) $row->event_date;
      $options[$date] = $date;
    }

    return $options;
  }

  private function getEventNameOptions(string $category, string $event_date): array {
    $today = date('Y-m-d', $this->time->getCurrentTime());
    $query = $this->database->select('event_configurations', 'c')
      ->fields('c', ['id', 'event_name'])
      ->condition('c.category', $category)
      ->condition('c.event_date', $event_date)
      ->condition('c.reg_start_date', $today, '<=')
      ->condition('c.reg_end_date', $today, '>=')
      ->orderBy('event_name', 'ASC');

    $result = $query->execute();

    $options = [];
    foreach ($result as $row) {
      $options[(int) $row->id] = (string) $row->event_name;
    }

    return $options;
  }

  private function hasOpenEvents(): bool {
    $today = date('Y-m-d', $this->time->getCurrentTime());
    $count = $this->database->select('event_configurations', 'c')
      ->condition('c.reg_start_date', $today, '<=')
      ->condition('c.reg_end_date', $today, '>=')
      ->countQuery()
      ->execute()
      ->fetchField();

    return (int) $count > 0;
  }

}
