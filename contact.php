<?php
    $contact->names[0]->familyName = $post["f_name"];
    $contact->names[0]->givenName = $post["l_name"];
    $contact->names[0]->displayName = $post["f_name"] . ' ' . $post["l_name"];
    $contact->names[0]->displayNameLastFirst = $post["l_name"] . ', ' . $post["f_name"];
    $contact->names[0]->unstructuredName = $post["f_name"] . ' ' . $post["l_name"];

    $i = 0;
    $emails = $post["email"];
    foreach (explode(', ', $emails) as $value) {
        $contact->emailAddresses[$i]->value = $value;
        $i = $i + 1;
    }

    $e = 0;
    $tels = $post["tel"];
    foreach (explode(', ', $tels) as $value) {
        $contact->phoneNumbers[$e]->value = $value;
        $e = $e + 1;
    }

    $contact->organizations[0]->name = $post["org"];
    $contact->organizations[0]->title = $post["role"];
    $contact->save();
?>