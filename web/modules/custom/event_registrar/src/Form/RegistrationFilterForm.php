<?php

declare(strict_types=1);

namespace Drupal\event_registrar\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationFilterForm extends FormBase {

  private Connection $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database')
    );
  }

  public function getFormId(): string {
    return 'event_registrar_registration_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $selected_date = (string) $form_state->getValue('event_date');
    $selected_event_name_id = $form_state->getValue('event_name_id');

    $form['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#required' => FALSE,
      '#options' => $this->getEventDateOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $selected_date ?: NULL,
      '#ajax' => [
        'callback' => '::updateEventName',
        'wrapper' => 'event-name-wrapper',
      ],
    ];

    $form['event_name_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-name-wrapper'],
    ];

    $form['event_name_wrapper']['event_name_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#required' => FALSE,
      '#options' => $selected_date ? $this->getEventNameOptionsForDate($selected_date) : [],
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $selected_event_name_id ?: NULL,
      '#ajax' => [
        'callback' => '::updateResults',
        'wrapper' => 'results-wrapper',
      ],
    ];

    $form['results_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'results-wrapper'],
    ];

    $participants_count = 0;
    $rows = [];

    if ($selected_date && $selected_event_name_id) {
      $rows = $this->getRegistrationRows($selected_date, (int) $selected_event_name_id);
      $participants_count = count($rows);
    }

    $form['results_wrapper']['participants'] = [
      '#type' => 'item',
      '#title' => $this->t('Total participants'),
      '#markup' => (string) $participants_count,
    ];

    $form['results_wrapper']['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Event Date'),
        $this->t('College Name'),
        $this->t('Department'),
        $this->t('Submission Date'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No registrations found.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['export_csv'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export CSV'),
      '#submit' => ['::exportCsv'],
      '#name' => 'export_csv',
    ];

    return $form;
  }

  public function updateEventName(array &$form, FormStateInterface $form_state): array {
    $form_state->setValue('event_name_id', NULL);
    return $form['event_name_wrapper'];
  }

  public function updateResults(array &$form, FormStateInterface $form_state): array {
    return $form['results_wrapper'];
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    if (($trigger['#name'] ?? '') === 'export_csv') {
      if (!$form_state->getValue('event_date')) {
        $form_state->setErrorByName('event_date', $this->t('Select an event date.'));
      }
      if (!$form_state->getValue('event_name_id')) {
        $form_state->setErrorByName('event_name_id', $this->t('Select an event name.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // No-op; this form is primarily AJAX-driven.
  }

  public function exportCsv(array &$form, FormStateInterface $form_state): void {
    $event_date = (string) $form_state->getValue('event_date');
    $event_name_id = (int) $form_state->getValue('event_name_id');
    $rows = $this->getRegistrationRowsForCsv($event_date, $event_name_id);

    $output = fopen('php://temp', 'wb');
    fputcsv($output, ['Name', 'Email', 'College Name', 'Department', 'Category', 'Event Date', 'Event Name', 'Submission Date']);

    foreach ($rows as $row) {
      fputcsv($output, $row);
    }

    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);

    $response = new Response($csv);
    $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="event_registrations.csv"');

    $form_state->setResponse($response);
  }

  private function getEventDateOptions(): array {
    $result = $this->database->select('event_registrations', 'r')
      ->fields('r', ['event_date'])
      ->distinct()
      ->orderBy('event_date', 'DESC')
      ->execute();

    $options = [];
    foreach ($result as $row) {
      $date = (string) $row->event_date;
      $options[$date] = $date;
    }

    return $options;
  }

  private function getEventNameOptionsForDate(string $event_date): array {
    $query = $this->database->select('event_configurations', 'c');
    $query->join('event_registrations', 'r', 'r.event_name_id = c.id');

    $query->fields('c', ['id', 'event_name']);
    $query->condition('r.event_date', $event_date);
    $query->distinct();
    $query->orderBy('c.event_name', 'ASC');

    $result = $query->execute();

    $options = [];
    foreach ($result as $row) {
      $options[(int) $row->id] = (string) $row->event_name;
    }

    return $options;
  }

  private function getRegistrationRows(string $event_date, int $event_name_id): array {
    $result = $this->database->select('event_registrations', 'r')
      ->fields('r', ['full_name', 'email', 'event_date', 'college_name', 'department', 'created'])
      ->condition('r.event_date', $event_date)
      ->condition('r.event_name_id', $event_name_id)
      ->orderBy('r.created', 'DESC')
      ->execute();

    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        (string) $row->full_name,
        (string) $row->email,
        (string) $row->event_date,
        (string) $row->college_name,
        (string) $row->department,
        date('Y-m-d H:i:s', (int) $row->created),
      ];
    }

    return $rows;
  }

  private function getRegistrationRowsForCsv(string $event_date, int $event_name_id): array {
    $query = $this->database->select('event_registrations', 'r');
    $query->join('event_configurations', 'c', 'c.id = r.event_name_id');
    $query->fields('r', ['full_name', 'email', 'college_name', 'department', 'created', 'event_date', 'category']);
    $query->fields('c', ['event_name']);
    $query->condition('r.event_date', $event_date);
    $query->condition('r.event_name_id', $event_name_id);
    $query->orderBy('r.created', 'DESC');

    $result = $query->execute();

    $rows = [];
    foreach ($result as $row) {
      $rows[] = [
        (string) $row->full_name,
        (string) $row->email,
        (string) $row->college_name,
        (string) $row->department,
        (string) $row->category,
        (string) $row->event_date,
        (string) $row->event_name,
        date('Y-m-d H:i:s', (int) $row->created),
      ];
    }

    return $rows;
  }

}
