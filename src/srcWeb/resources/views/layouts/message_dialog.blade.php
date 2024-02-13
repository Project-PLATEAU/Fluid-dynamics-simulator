<!-- Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="messageModalLabel"></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- 共通に使用するため、動的に設定する。 --}}
                {{-- <div class="d-flex flex-row">
                    <img class="ms-2" src="{{ asset('/image/dialog/warning.png') }}?ver={{ config('const.ver_image') }}"
                            height="75px" width="65px" alt="warning">
                    <span class="ms-4">{{ $message ? $message : null }}</span>
                </div> --}}
            </div>
            <div class="modal-footer">
                 {{-- 共通に使用するため、動的に設定する。 --}}
                {{-- <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary">OK</button> --}}
            </div>
        </div>
    </div>
</div>
