<?php

declare(strict_types=1);

namespace Drupal\event_registrar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

final class NotificationSettingsForm extends ConfigFormBase {

  public function getFormId(): string {
    return 'event_registrar_notification_settings_form';
  }

  protected function getEditableConfigNames(): array {
    return ['event_registrar.notification_settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('event_registrar.notification_settings');

    $form['admin_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Admin notification email address'),
      '#default_value' => (string) $config->get('admin_email'),
      '#required' => TRUE,
    ];

    $form['enable_admin_notifications'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable admin notifications'),
      '#default_value' => (bool) $config->get('enable_admin_notifications'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('event_registrar.notification_settings')
      ->set('admin_email', (string) $form_state->getValue('admin_email'))
      ->set('enable_admin_notifications', (bool) $form_state->getValue('enable_admin_notifications'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
