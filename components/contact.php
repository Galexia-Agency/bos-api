<?php
    if (!$contact->names[0]) {
        $contact->names[0] = new stdClass;
    }
    $contact->names[0]->familyName = $post["l_name"];
    $contact->names[0]->givenName = $post["f_name"];
    $contact->names[0]->displayName = $post["f_name"] . ' ' . $post["l_name"];
    $contact->names[0]->displayNameLastFirst = $post["l_name"] . ', ' . $post["f_name"];
    $contact->names[0]->unstructuredName = $post["f_name"] . ' ' . $post["l_name"];
    if (!$contact->organizations[0]) {
        $contact->organizations[0] = new stdClass;
    }
    $contact->organizations[0]->name = $post["org"];
    $contact->organizations[0]->title = $post["role"];
    $contact->organizations[0]->title = $post["role"];

    $i = 0;
    $emails = $post["email"];
    foreach (explode(', ', $emails) as $value) {
        if (!$contact->emailAddresses[$i]) {
            $contact->emailAddresses[$i] = new stdClass;
        }
        $contact->emailAddresses[$i]->value = $value;
        $i = $i + 1;
    }

    $e = 0;
    $tels = $post["tel"];
    foreach (explode(', ', $tels) as $value) {
        if (!$contact->phoneNumbers[$e]) {
            $contact->phoneNumbers[$e] = new stdClass;
        }
        $contact->phoneNumbers[$e]->value = $value;
        $e = $e + 1;
    }

    if ($post["address"]) {
        $address = json_decode($post["address"]);
        if (!$contact->addresses[0]) {
            $contact->addresses[0] = new stdClass;
        }
        $contact->addresses[0]->type = 'work';
        $contact->addresses[0]->streetAddress = $address->line1;
        $contact->addresses[0]->extendedAddress = $address->line2 . ' ' . $address->line3;
        $contact->addresses[0]->city = $address->town;
        $contact->addresses[0]->postalCode = $address->postcode;
        $contact->addresses[0]->country = $address->country;
    }

    $contact->save();
?>