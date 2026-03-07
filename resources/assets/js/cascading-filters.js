/**
 * Cascading Filters for Reports
 * Handles dependent dropdowns: Campus -> Faculty -> Department -> Program -> Year -> Course/Group
 */

document.addEventListener('DOMContentLoaded', function () {
  const campusSelect = document.querySelector('select[name="campus_id"]');
  const facultySelect = document.querySelector('select[name="faculty_id"]');
  const departmentSelect = document.querySelector('select[name="department_id"]');
  const programSelect = document.querySelector('select[name="program_id"]');
  const yearSelect = document.querySelector('select[name="year_of_study"]');
  const courseSelect = document.querySelector('select[name="course_id"]');
  const groupSelect = document.querySelector('select[name="group_id"]');

  // Helper function to update a select dropdown
  function updateSelect(selectElement, items, valueKey = 'id', textKey = 'name', placeholder = 'All') {
    if (!selectElement) return;

    const currentValue = selectElement.value;
    const firstOption = selectElement.querySelector('option:first-child');
    const placeholderText = firstOption ? firstOption.textContent : `All ${placeholder}`;

    selectElement.innerHTML = `<option value="">${placeholderText}</option>`;

    items.forEach(item => {
      const option = document.createElement('option');
      option.value = item[valueKey];
      option.textContent = item.code ? `${item.code} - ${item[textKey]}` : item[textKey];
      if (item[valueKey] == currentValue) {
        option.selected = true;
      }
      selectElement.appendChild(option);
    });
  }

  // Campus change -> Load Faculties
  if (campusSelect) {
    campusSelect.addEventListener('change', function () {
      const campusId = this.value;

      if (!campusId) {
        // Reset all dependent dropdowns
        if (facultySelect) updateSelect(facultySelect, [], 'id', 'name', 'Faculties');
        if (departmentSelect) updateSelect(departmentSelect, [], 'id', 'name', 'Departments');
        if (programSelect) updateSelect(programSelect, [], 'id', 'name', 'Programs');
        if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
        if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        return;
      }

      // Fetch faculties for this campus
      fetch(`/admin/api/faculties-by-campus?campus_id=${campusId}`)
        .then(response => response.json())
        .then(faculties => {
          updateSelect(facultySelect, faculties, 'id', 'name', 'Faculties');
          // Reset dependent dropdowns
          if (departmentSelect) updateSelect(departmentSelect, [], 'id', 'name', 'Departments');
          if (programSelect) updateSelect(programSelect, [], 'id', 'name', 'Programs');
          if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
          if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        })
        .catch(error => console.error('Error fetching faculties:', error));
    });
  }

  // Faculty change -> Load Departments
  if (facultySelect) {
    facultySelect.addEventListener('change', function () {
      const facultyId = this.value;

      if (!facultyId) {
        if (departmentSelect) updateSelect(departmentSelect, [], 'id', 'name', 'Departments');
        if (programSelect) updateSelect(programSelect, [], 'id', 'name', 'Programs');
        if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
        if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        return;
      }

      // Fetch departments for this faculty
      fetch(`/admin/api/departments-by-faculty?faculty_id=${facultyId}`)
        .then(response => response.json())
        .then(departments => {
          updateSelect(departmentSelect, departments, 'id', 'name', 'Departments');
          // Reset dependent dropdowns
          if (programSelect) updateSelect(programSelect, [], 'id', 'name', 'Programs');
          if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
          if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        })
        .catch(error => console.error('Error fetching departments:', error));
    });
  }

  // Department change -> Load Programs
  if (departmentSelect) {
    departmentSelect.addEventListener('change', function () {
      const departmentId = this.value;

      if (!departmentId) {
        if (programSelect) updateSelect(programSelect, [], 'id', 'name', 'Programs');
        if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
        if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        return;
      }

      // Fetch programs for this department
      fetch(`/admin/api/programs-by-department?department_id=${departmentId}`)
        .then(response => response.json())
        .then(programs => {
          updateSelect(programSelect, programs, 'id', 'name', 'Programs');
          // Reset dependent dropdowns
          if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
          if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
        })
        .catch(error => console.error('Error fetching programs:', error));
    });
  }

  // Program or Year change -> Load Courses and Groups
  function loadCoursesAndGroups() {
    const programId = programSelect ? programSelect.value : null;
    const yearOfStudy = yearSelect ? yearSelect.value : null;

    if (!programId) {
      if (courseSelect) updateSelect(courseSelect, [], 'id', 'name', 'Courses');
      if (groupSelect) updateSelect(groupSelect, [], 'id', 'name', 'Groups');
      return;
    }

    // Fetch courses
    if (courseSelect) {
      let url = `/admin/api/courses-by-program?program_id=${programId}`;
      if (yearOfStudy) url += `&year_of_study=${yearOfStudy}`;

      fetch(url)
        .then(response => response.json())
        .then(courses => {
          updateSelect(courseSelect, courses, 'id', 'name', 'Courses');
        })
        .catch(error => console.error('Error fetching courses:', error));
    }

    // Fetch groups
    if (groupSelect) {
      let url = `/admin/api/groups-by-program?program_id=${programId}`;
      if (yearOfStudy) url += `&year_of_study=${yearOfStudy}`;

      fetch(url)
        .then(response => response.json())
        .then(groups => {
          updateSelect(groupSelect, groups, 'id', 'name', 'Groups');
        })
        .catch(error => console.error('Error fetching groups:', error));
    }
  }

  if (programSelect) {
    programSelect.addEventListener('change', loadCoursesAndGroups);
  }

  if (yearSelect) {
    yearSelect.addEventListener('change', loadCoursesAndGroups);
  }
});
