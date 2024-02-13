@foreach($stlFiles as $stlFile)
    <span class="stl" data-stl-type-id="{{ $stlFile->stl_type_id }}" onclick="setbgColor(this)">{{ $stlFile->stl_type->stl_type_name }}</span>
@endforeach
