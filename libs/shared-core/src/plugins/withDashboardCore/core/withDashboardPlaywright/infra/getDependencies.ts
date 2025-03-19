export const getDependencies = (depCommits: Record<string, string>) => {
  const entries = Object.entries(depCommits);

  const dependencies = entries.map(([depName, commit]) => {
    const dependency: any = {
      name: depName,
      commit_id: commit,
    };

    // TODO: Make this changes dyamic from depCommits payload;
    if (depName === 'api') {
      dependency.chart_values = {
        replicas: 3,
        enable_edge_base: true,
      };
    }
    if (depName === 'splitz') {
      // dependency.chart_values = {
      // splitz_replicas: 1,
      // splitz_worker_replicas: 1,
      // };
    }
    if (depName === 'terminals') {
      dependency.chart_values = {
        ephemeral_db: true,
        // terminals_live_replicas: 1,
        // terminals_test_replicas: 1,
      };
    }
    if (depName === 'settlements') {
      dependency.chart_values = {
        // enable only settlements_live_replicas (disable the rest)
        settlements_test_replicas: 0,
        settlements_create_test_worker_replicas: 0,
        settlements_create_live_worker_replicas: 0,
        transactions_recorder_live_worker_replicas: 0,
        transactions_recorder_live_dlq_worker_replicas: 0,
        transactions_recorder_test_worker_replicas: 0,
        transactions_recorder_test_dlq_worker_replicas: 0,
        transactions_update_live_worker_replicas: 0,
        transactions_update_test_worker_replicas: 0,
        settlements_initiate_live_worker_replicas: 0,
        settlements_initiate_test_worker_replicas: 0,
        settlements_retry_live_worker_replicas: 0,
        settlements_retry_test_worker_replicas: 0,
        settlements_status_update_live_replicas: 0,
        settlements_status_update_test_replicas: 0,
        settlements_trigger_live_worker_replicas: 0,
        settlements_trigger_test_worker_replicas: 0,
        execution_verify_live_worker_replicas: 0,
        execution_verify_test_worker_replicas: 0,
        settlements_report_notification_live_worker_replica: 0,
        settlements_report_notification_test_worker_replica: 0,
        settlements_pagination_live_worker_replica: 0,
        settlements_pagination_test_worker_replica: 0,
        settlements_ledger_live_worker_replica: 0,
        settlements_ledger_test_worker_replica: 0,
        settlements_entity_alert_live_worker_replica: 0,
        settlements_entity_alert_test_worker_replica: 0,
        settlements_entity_alert_live_worker_replicas: 0,
        optimizer_transactions_recorder_test_worker_replicas: 0,
        optimizer_transactions_recorder_live_worker_replicas: 0,
      };
    }

    if (depName === 'payment-links') {
      // es_replicas, expire_replicas, webhook_replicas are required (and set to 1 by default).
      dependency.chart_values = {
        run_es_in_sync: '1',
        email_replicas: 0,
        merchantrisk_replicas: 0,
        reminder_replicas: 0,
        sms_replicas: 0,
        reminderscallback_replicas: 0,
        paymentfailedretry_replicas: 0,
        whatsapp_replicas: 0,
        capturedpaymentslive_replicas: 0,
      };
    }
    if (depName === 'pg-router') {
      dependency.chart_values = {
        pgrouter_worker_create_transaction_replicas: 0,
        pgrouter_worker_register_payment_replicas: 0,
        pgrouter_worker_register_payment_rearch_replicas: 0,
        pgrouter_worker_order_update_replicas: 0,
        pgrouter_worker_notification_replicas: 0,
        pgrouter_worker_outbox_relay_replicas: 0,
        pgrouter_worker_ledger_replicas: 0,
        pgrouter_worker_pos_notification_update_replicas: 0,
        pgrouter_worker_order_translator_pos_event_replicas: 0,
        pgrouter_app_mode: 'live',
      };
    }
    if (depName === 'ui-config-service') {
      dependency.chart_values = {
        service_account_enabled: false,
      };
    }

    if (depName === 'no-code-apps') {
      dependency.chart_values = {
        worker_replicas: 0,
      };
    }

    return dependency;
  });

  return dependencies;
};
