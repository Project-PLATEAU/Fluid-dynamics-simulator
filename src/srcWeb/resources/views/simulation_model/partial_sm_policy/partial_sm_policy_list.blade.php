<table class="table table-hover" id="tblSmPolicy">
    <thead>
        <tr>
            <th scope="col">施策</th>
            <th scope="col">対象建物・地表面</th>
        </tr>
    </thead>
    <tbody class="table-group-divider">
        @foreach ($smPolicies as $index => $smPolicy)
            <tr onclick="toggleTr(this)">
                <td>{{ $smPolicy->policy->policy_name }}</td>
                <td>{{ $smPolicy->stl_type->stl_type_name }}</td>
                <input type="hidden" id="smPolicySimulationModelId" name="simulationModelPolicy[{{ $index }}][simulation_model_id]" value="{{ $simulationModelId }}">
                <input type="hidden" id="smPolicyPolicyId" name="simulationModelPolicy[{{ $index }}][policy_id]" value="{{ $smPolicy->policy_id }}">
                <input type="hidden" id="smPolicyStlTypeId" name="simulationModelPolicy[{{ $index }}][stl_type_id]" value="{{ $smPolicy->stl_type_id }}">
            </tr>
        @endforeach
    </tbody>
</table>
