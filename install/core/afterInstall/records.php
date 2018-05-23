<?php


return array(
	'EmailTemplate' => array(
		array(
			'name' => 'Case-to-Email auto-reply',
			'subject' => 'Case has been created',
			'body' => '<p>{Person.name},</p><p>Case \'{Case.name}\' has been created with number {Case.number} and assigned to {User.name}.</p>',
			'isHtml ' => '1',
		),
	),
	'ScheduledJob' => array(
		array(
			'name' => 'Check Group Email Accounts',
			'job' => 'CheckInboundEmails',
			'status' => 'Active',
			'scheduling' => '*/4 * * * *',
		),
		array(
			'name' => 'Check Personal Email Accounts',
			'job' => 'CheckEmailAccounts',
			'status' => 'Active',
			'scheduling' => '*/5 * * * *',
		),
		array(
			'name' => 'Send Email Reminders',
			'job' => 'SendEmailReminders',
			'status' => 'Active',
			'scheduling' => '*/2 * * * *',
		),
		array(
			'name' => 'Send Email Notifications',
			'job' => 'SendEmailNotifications',
			'status' => 'Active',
			'scheduling' => '*/2 * * * *',
		),
		array(
			'name' => 'Clean-up',
			'job' => 'Cleanup',
			'status' => 'Active',
			'scheduling' => '1 1 * * 0',
		),
		array(
			'name' => 'Send Mass Emails',
			'job' => 'ProcessMassEmail',
			'status' => 'Active',
			'scheduling' => '15 * * * *',
		),
		array(
			'name' => 'Auth Token Control',
			'job' => 'AuthTokenControl',
			'status' => 'Active',
			'scheduling' => '*/6 * * * *',
		),
		array(
			'name' => 'Control Knowledge Base Article Status',
			'job' => 'ControlKnowledgeBaseArticleStatus',
			'status' => 'Active',
			'scheduling' => '10 1 * * *',
		)
	),
);