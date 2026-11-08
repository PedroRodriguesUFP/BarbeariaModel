{{--
    Carrossel 3D das fotos de um serviço.
    Lê as imagens de public/img/servicos/{slug-do-nome}/ (ver LEIA-ME.md nessa pasta).

    Uso: <x-service-carousel :service="$service" />
         <x-service-carousel :service="$service" compact />
--}}
@props(['service', 'compact' => false])

@php
    $slug = \Illuminate\Support\Str::slug($service->name);
    $dir = public_path('img/servicos/'.$slug);
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'];

    $images = collect(is_dir($dir) ? \Illuminate\Support\Facades\File::files($dir) : [])
        ->filter(fn ($file) => in_array(strtolower($file->getExtension()), $extensions, true))
        ->sortBy(fn ($file) => $file->getFilename(), SORT_NATURAL | SORT_FLAG_CASE)
        ->map(fn ($file) => asset('img/servicos/'.$slug.'/'.$file->getFilename()))
        ->values();

    // Sem fotos: espaços reservados só em ambiente local, para saberes onde pôr as imagens.
    $placeholders = $images->isEmpty() && app()->isLocal();

    $originals = $placeholders ? 5 : $images->count();

    // Com poucas fotos o 3D fica vazio: repete-as (cópias escondidas dos leitores de ecrã) até haver 5 posições.
    $slides = $placeholders ? collect(range(1, 5))->map(fn () => null) : $images;
    if ($originals > 1) {
        while ($slides->count() < 5) {
            $slides = $slides->concat($placeholders ? array_fill(0, 5, null) : $images);
        }
    }
    $total = $slides->count();
    $id = 'c3d-'.$service->id;
@endphp

@if ($originals > 0)
    <section
        id="{{ $id }}"
        class="c3d {{ $compact ? 'c3d--compact' : '' }} {{ $originals < 2 ? 'is-single' : '' }}"
        data-c3d
        @unless ($compact) data-autoplay="4500" @endunless
        role="region"
        aria-roledescription="carrossel"
        aria-label="Fotografias de {{ $service->name }}"
        data-no-tilt
    >
        <div class="c3d__stage" data-c3d-stage tabindex="0" aria-label="Use as setas do teclado para mudar de foto">
            <div class="c3d__floor" aria-hidden="true"></div>

            <ul class="c3d__track" data-c3d-track>
                @foreach ($slides as $i => $src)
                    @php($isClone = $i >= $originals)
                    <li
                        class="c3d__slide {{ $placeholders ? 'is-placeholder' : '' }}"
                        data-c3d-slide
                        style="--o: {{ $i }}; --abs: {{ $i }};"
                        role="group"
                        aria-roledescription="foto"
                        aria-label="{{ ($i % $originals) + 1 }} de {{ $originals }}"
                        @if ($isClone) aria-hidden="true" data-clone @endif
                    >
                        @if ($src)
                            <img
                                src="{{ $src }}"
                                alt="{{ $isClone ? '' : $service->name.', foto '.(($i % $originals) + 1) }}"
                                loading="{{ $i < 3 ? 'eager' : 'lazy' }}"
                                decoding="async"
                                draggable="false"
                            >
                        @else
                            <div class="c3d__empty">
                                <strong>Foto {{ ($i % $originals) + 1 }}</strong>
                                <span>public/img/servicos/{{ $slug }}/</span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        @if ($originals > 1)
            <div class="c3d__controls">
                <button class="c3d__btn" type="button" data-c3d-prev aria-label="Foto anterior">&#8592;</button>

                <div class="c3d__dots" data-c3d-dots aria-hidden="true">
                    @for ($d = 0; $d < $originals; $d++)
                        <span class="c3d__dot"></span>
                    @endfor
                </div>

                <button class="c3d__btn" type="button" data-c3d-next aria-label="Foto seguinte">&#8594;</button>

                @unless ($compact)
                    <button class="c3d__btn c3d__btn--play" type="button" data-c3d-toggle aria-pressed="false" aria-label="Pausar rotação automática">&#10074;&#10074;</button>
                @endunless
            </div>

            <p class="c3d__live" data-c3d-live aria-live="polite" aria-atomic="true"></p>
        @endif
    </section>
@endif
