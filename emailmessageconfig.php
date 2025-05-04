<?php

// This file needs to define $LOAN_REMINDER_FROM as the address that you want the email
// to come from. Replies will also go to this address.

//$LOAN_REMINDER_FROM = "fred@bws.com";

// This file also needs to define the messages that will be sent to the borrowers. If you want
// only one message sent until the DVD is returned, then define only $message[1]. If you want
// a series of different messages sent until the return, then define more messages as
// $message[1], $message[2], $message[3], etc. The program sends $message[1], next it sends
// $message[2], etc. until it reaches the end, and then it continues to send the last message.
//
// The same thing applies to the subjects of the sent messages, using $subject[1], etc. The number
// of different subjects need not match the number of different messages.
//
// The messages and subjects may use some special variables to allow personalisation:
// $LOAN_TO     - The borrower's name (from DVDProfiler)
// $LOAN_TITLE      - The title of the borrowed DVD
// $LOAN_QUOTED_TITLE   - The title of the DVD in quotes
// $LOAN_DATE_BORROWED  - The date the DVD was borrowed (from DVDProfiler)
// $LOAN_DATE_DUE   - The date the DVD was due to be returned (from DVDProfiler)
// $LOAN_GRACE      - The grace period before any email reminders are sent (in days)
// $LOAN_WARNING_INTERVAL - The frequency with which email warnings are sent (in days)
// $LOAN_REMINDER_TO    - The email address where the reminder is being sent (from DVDProfiler)
// $LOAN_WARNING_NUMBER - The number of the current warning (is the message number to be sent)
// $NOW         - The current date

$subject[1] = "You borrowed $LOAN_QUOTED_TITLE on $LOAN_DATE_BORROWED";

if ($LOAN_GRACE == 0) {
    $message[1] = <<<EOT
Hi $LOAN_TO,
    You've had $LOAN_QUOTED_TITLE since $LOAN_DATE_BORROWED. It was due back on $LOAN_DATE_DUE. It'd be great to get it back. I'll send another note in $LOAN_WARNING_INTERVAL days.

Cheers!

Fred
EOT;
}
else {
    $message[1] = <<<EOT
Hi $LOAN_TO,
    You've had $LOAN_QUOTED_TITLE since $LOAN_DATE_BORROWED. It was due back on $LOAN_DATE_DUE. I thought I'd wait $LOAN_GRACE days before mentioning it. It'd be great to get it back. I'll send another note in $LOAN_WARNING_INTERVAL days.

Cheers!

Fred
EOT;
}

$message[2] = <<<EOT
Good Day,
    You've had $LOAN_QUOTED_TITLE since $LOAN_DATE_BORROWED. It was due back on $LOAN_DATE_DUE and it's now $NOW. It'd _really_ be great to get it back. Soon, please. I'll send another note in $LOAN_WARNING_INTERVAL days in case you missed my point.

Cheers!

Fred
EOT;

$message[3] = <<<EOT
Hey Butt-Nugget,
    You've had $LOAN_QUOTED_TITLE since $LOAN_DATE_BORROWED. You said you'd return it by $LOAN_DATE_DUE and it's now $NOW!!! I'm sending over Guido and Nunzio, the broken-nose twins to extract the DVD from your miserable carcass!!!

Have a great day

Fred
EOT;
