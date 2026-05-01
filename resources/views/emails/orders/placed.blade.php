<x-mail::message>
# Ordrer Placed Successfully

TThank you for your order. You order number is {{ $order->id }}.

<x-mail::button :url="$url">
View Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
