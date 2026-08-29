import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import HomeView from "../views/HomeView.vue";
import LoginView from "../views/LoginView.vue";
import RegisterView from "../views/RegisterView.vue";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: "/", name: "landing", component: HomeView },
    { path: "/login", name: "login", component: LoginView },
    { path: "/register", name: "register", component: RegisterView },
    {
      path: "/dashboard",
      name: "dashboard",
      component: () => import("../views/DashboardView.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/cvs/new",
      name: "cv-new",
      component: () => import("../views/CvFormView.vue"),
      meta: { requiresAuth: true },
    },
    {
      path: "/cvs/:id/edit",
      name: "cv-edit",
      component: () => import("../views/CvFormView.vue"),
      props: true,
      meta: { requiresAuth: true },
    },
  ],
});

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true;

  const auth = useAuthStore();
  if (!auth.isAuthenticated) await auth.fetchUser();
  if (!auth.isAuthenticated) return { name: "login" };
});

export default router;
