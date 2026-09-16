<x-mail::message>
# One more thing

Thanks for writing about **{{ $recipe?->name }}**. Before it goes any further,
tap the button so I know the address is yours.

<x-mail::button :url="$url">
Confirm my review
</x-mail::button>

Once you have, it joins the queue and I read it before it goes on the page.
Until then nobody sees it and it counts towards nothing.

If you did not write a review on {{ config('app.name') }}, somebody typed your
address in by mistake. Ignore this and nothing happens - the review stays
where it is and is thrown out on its own.

The link works for {{ $days }} {{ Str::plural('day', $days) }}.

Thanks,<br>
{{ config('app.name') }}

<x-slot:subcopy>
If the button does not work, copy this into your browser: [{{ $url }}]({{ $url }})
</x-slot:subcopy>
</x-mail::message>
