<div class="col-md-6">
    <label for="project_id" class="form-label fw-bold text-dark font-sm">Relasikan ke Project</label>
    <select name="project_id" id="project_id" class="form-select smooth-dropdown">
        <option value="">-- Umum / Operasional (tidak terikat project) --</option>
        @foreach($projects as $project)
            <option value="{{ $project->id }}" {{ (string) old('project_id', $selectedProjectId ?? '') === (string) $project->id ? 'selected' : '' }}>
                {{ $project->project_name }}{{ $project->client?->name ? ' — ' . $project->client->name : '' }}
            </option>
        @endforeach
    </select>
    <small class="text-muted font-xs">Transaksi yang tidak dipilih menjadi kas umum di luar perhitungan margin project.</small>
</div>
