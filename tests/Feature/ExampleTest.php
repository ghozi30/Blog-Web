<?php

test('home redirects to blog', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('blog.index'));
});

test('blog page returns a successful response', function () {
    $this->get(route('blog.index'))->assertOk();
});
