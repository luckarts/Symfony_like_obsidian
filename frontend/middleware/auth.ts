export default defineNuxtRouteMiddleware((to) => {
  const store = useAuthStore();
  store.hydrate();
  if (!store.isAuthenticated) {
    return navigateTo({
      path: "/auth/login",
      query: { redirect: to.fullPath },
    });
  }
});
