<div class="flex justify-end">
    <x-report-modal
        type="user"
        :entityId="$user->id"
        :entityName="$user->name"
        :action="route('reports.users.store', $user)"
        :reasons="\App\Models\Report::USER_REASONS"
        :existingReport="$viewerReport"
    />
</div>
