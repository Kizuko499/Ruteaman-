import {mountAdminSidebar} from './features/admin/sidebar';
import {mountSchoolDashboard} from './features/school/dashboard';
import {mountSchoolReauth} from './features/school/reauth';
mountAdminSidebar();mountSchoolReauth();document.querySelectorAll('[data-school-dashboard],[data-school-shell]').forEach(mountSchoolDashboard);
