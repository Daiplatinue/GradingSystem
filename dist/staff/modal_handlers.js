function showGradesModal(student) {
    currentStudent = student;
    document.getElementById('viewModalStudentName').textContent = student.a_fn;

    // Update science grades
    document.getElementById('viewScience1').textContent = student.g_science1 || '-';
    document.getElementById('viewScience2').textContent = student.g_science2 || '-';
    document.getElementById('viewScience3').textContent = student.g_science3 || '-';
    document.getElementById('viewScience4').textContent = student.g_science4 || '-';

    // Update math grades
    document.getElementById('viewMath1').textContent = student.g_math1 || '-';
    document.getElementById('viewMath2').textContent = student.g_math2 || '-';
    document.getElementById('viewMath3').textContent = student.g_math3 || '-';
    document.getElementById('viewMath4').textContent = student.g_math4 || '-';

    // Update programming grades
    document.getElementById('viewProg1').textContent = student.g_programming1 || '-';
    document.getElementById('viewProg2').textContent = student.g_programming2 || '-';
    document.getElementById('viewProg3').textContent = student.g_programming3 || '-';
    document.getElementById('viewProg4').textContent = student.g_programming4 || '-';

    // Update reed grades
    document.getElementById('viewReed1').textContent = student.g_reed1 || '-';
    document.getElementById('viewReed2').textContent = student.g_reed2 || '-';
    document.getElementById('viewReed3').textContent = student.g_reed3 || '-';
    document.getElementById('viewReed4').textContent = student.g_reed4 || '-';

    // Update period averages
    document.getElementById('viewPrelim').textContent = student.g_prelim || '-';
    document.getElementById('viewMidterm').textContent = student.g_midterm || '-';
    document.getElementById('viewPrefinal').textContent = student.g_prefinal || '-';
    document.getElementById('viewFinal').textContent = student.g_final || '-';

    // Update final grade
    document.getElementById('viewFinalGrade').textContent = student.g_total || '-';

    document.getElementById('viewGradesModal').classList.remove('hidden');
}

function showEditModal(student) {
    currentStudent = student;
    document.getElementById('editModalStudentName').textContent = student.a_fn;
    document.getElementById('modalStudentId').value = student.a_id;
    updatePeriodFields();
    document.getElementById('editGradeModal').classList.remove('hidden');
    document.getElementById('viewGradesModal').classList.add('hidden');
}

function closeViewModal() {
    document.getElementById('viewGradesModal').classList.add('hidden');
}

function closeEditModal() {
    document.getElementById('editGradeModal').classList.add('hidden');
}

function updatePeriodFields() {
    const periodSelect = document.getElementById('gradePeriod');
    const period = periodSelect.value;
    const periodName = periodSelect.options[periodSelect.selectedIndex].dataset.name;

    document.getElementById('modalPeriod').value = period;
    document.getElementById('modalPeriodName').value = periodName;

    if (currentStudent) {
        switch (period) {
            case '1':
                document.getElementById('modalScience').value = currentStudent.g_science1 || '';
                document.getElementById('modalMath').value = currentStudent.g_math1 || '';
                document.getElementById('modalProgramming').value = currentStudent.g_programming1 || '';
                document.getElementById('modalReed').value = currentStudent.g_reed1 || '';
                break;
            case '2':
                document.getElementById('modalScience').value = currentStudent.g_science2 || '';
                document.getElementById('modalMath').value = currentStudent.g_math2 || '';
                document.getElementById('modalProgramming').value = currentStudent.g_programming2 || '';
                document.getElementById('modalReed').value = currentStudent.g_reed2 || '';
                break;
            case '3':
                document.getElementById('modalScience').value = currentStudent.g_science3 || '';
                document.getElementById('modalMath').value = currentStudent.g_math3 || '';
                document.getElementById('modalProgramming').value = currentStudent.g_programming3 || '';
                document.getElementById('modalReed').value = currentStudent.g_reed3 || '';
                break;
            case '4':
                document.getElementById('modalScience').value = currentStudent.g_science4 || '';
                document.getElementById('modalMath').value = currentStudent.g_math4 || '';
                document.getElementById('modalProgramming').value = currentStudent.g_programming4 || '';
                document.getElementById('modalReed').value = currentStudent.g_reed4 || '';
                break;
        }
    }
}

window.onclick = function(event) {
    const viewModal = document.getElementById('viewGradesModal');
    const editModal = document.getElementById('editGradeModal');
    if (event.target == viewModal) {
        closeViewModal();
    }
    if (event.target == editModal) {
        closeEditModal();
    }
}