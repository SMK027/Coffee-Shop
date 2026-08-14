@foreach($data as $name => $value)
    @php
        $fieldName = isset($prefix) ? $prefix . '[' . $name . ']' : (string) $name;
    @endphp

    @if(is_array($value))
        @include('employee.supervision.partials.hidden-fields', ['data' => $value, 'prefix' => $fieldName])
    @else
        <input type="hidden" name="{{ $fieldName }}" value="{{ (string) $value }}">
    @endif
@endforeach
