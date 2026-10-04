<?php

test('redirects guests from home to login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
