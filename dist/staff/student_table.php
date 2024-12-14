<table class="w-full">
    <thead>
        <tr class="border-b border-gray-200">
            <th class="px-6 py-4 text-left text-sm font-semibold text-[#86868b]">Full Name</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-[#86868b]">Email</th>
            <th class="px-6 py-4 text-left text-sm font-semibold text-[#86868b]">Grade</th>
            <th class="px-6 py-4 text-right text-sm font-semibold text-[#86868b]">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $student): ?>
            <tr class="border-b border-gray-100 hover:bg-[#f5f5f7] transition-colors">
                <td class="px-6 py-4 text-[#1d1d1f]"><?php echo htmlspecialchars($student['a_fn']); ?></td>
                <td class="px-6 py-4 text-[#1d1d1f]"><?php echo htmlspecialchars($student['a_email']); ?></td>
                <td class="px-6 py-4 text-[#1d1d1f]"><?php echo htmlspecialchars($student['a_grade']); ?></td>
                <td class="px-6 py-4 text-right space-x-2">
                    <button onclick="showGradesModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#0071e3] hover:bg-[#0077ed] transition-colors">
                        View Grades
                    </button>
                    <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                        class="inline-flex items-center px-4 py-2 border border-[#0071e3] text-sm font-medium rounded-lg text-[#0071e3] hover:bg-[#0071e3] hover:text-white transition-colors">
                        Edit Grades
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>