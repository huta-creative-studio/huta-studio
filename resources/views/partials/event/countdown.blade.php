<?php defined('BASEPATH')||exit('No direct script access allowed');?>@php
$hutaCountdownContext = function_exists('huta_view_countdown_context') ? huta_view_countdown_context((array) ($desa ?? [])) : ['event_date'=>'','event_description'=>'','event_summary'=>'','timezone'=>'Asia/Jakarta'];
$hutaEventDate=(string)($hutaCountdownContext['event_date']??'');
$hutaEventDescription=(string)($hutaCountdownContext['event_description']??'');
$hutaEventSummary=(string)($hutaCountdownContext['event_summary']??'');
$hutaNextEventDate=(string)($hutaCountdownContext['next_event_date']??'');
$hutaNextEventDescription=(string)($hutaCountdownContext['next_event_description']??'');
$hutaNextEventSummary=(string)($hutaCountdownContext['next_event_summary']??'');
$hutaCalendarUrl=(string)($hutaCountdownContext['calendar_url']??'https://raw.githubusercontent.com/ariandii/json/main/calendar.json');
$hutaTimezone=(string)($hutaCountdownContext['timezone']??'Asia/Jakarta');
@endphp
<div class="huta-countdown-runtime" data-custom-enabled="{{ $hutaEventDate!==''?'1':'0' }}" data-event-date="{{ $hutaEventDate }}" data-event-description="{{ $hutaEventDescription }}" data-event-summary="{{ $hutaEventSummary }}" data-next-event-date="{{ $hutaNextEventDate }}" data-next-event-description="{{ $hutaNextEventDescription }}" data-next-event-summary="{{ $hutaNextEventSummary }}" data-calendar-url="{{ $hutaCalendarUrl }}" data-timezone="{{ $hutaTimezone }}" role="timer" aria-live="polite" aria-atomic="true" hidden></div>
