<script setup lang="ts">
import { useField } from "~/composables/useField";
import { required } from "~/utils/validators";

defineProps<{
<<<<<<< HEAD
    loading: boolean;
=======
  loading: boolean;
>>>>>>> 24b007e (feat(auth): replace inline error with toast notifications on signup)
}>();

const emit = defineEmits<{
    submit: [
        {
            firstName: string;
            lastName: string;
            email: string;
            password: string;
        },
    ];
}>();

const {
    value: firstName,
    errorMessage: firstNameError,
    handleBlur: touchFirstName,
    validate: validateFirstName,
} = useField<string>("", [required("Le prénom est requis")]);

const {
    value: lastName,
    errorMessage: lastNameError,
    handleBlur: touchLastName,
    validate: validateLastName,
} = useField<string>("", [required("Le nom est requis")]);

const {
    value: email,
    errorMessage: emailError,
    handleBlur: touchEmail,
    validate: validateEmail,
} = useField<string>("", [required("L'email est requis")]);

const {
    value: password,
    errorMessage: passwordError,
    handleBlur: touchPassword,
    validate: validatePassword,
} = useField<string>("", [required("Le mot de passe est requis")]);

function handleSubmit() {
    const valid = [
        validateFirstName,
        validateLastName,
        validateEmail,
        validatePassword,
    ].every((fn) => fn());
    if (valid) {
        emit("submit", {
            firstName: firstName.value as string,
            lastName: lastName.value as string,
            email: email.value as string,
            password: password.value as string,
        });
    }
}
</script>

<template>
    <Card variant="shadow" class="w-full max-w-sm">
        <div class="flex flex-col items-center gap-3">
            <AppLogo size="md" />
            <Heading :level="1" size="lg">Créer un compte</Heading>
        </div>

        <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
            <div class="flex gap-3">
                <Input
                    v-model="firstName"
                    type="text"
                    name="firstName"
                    label="Prénom"
                    placeholder="Jean"
                    :error="firstNameError"
                    :disabled="loading"
                    class="w-[35%]"
                    @blur="touchFirstName()"
                />

                <Input
                    v-model="lastName"
                    type="text"
                    name="lastName"
                    label="Nom"
                    placeholder="Dupont"
                    :error="lastNameError"
                    :disabled="loading"
                    class="w-[61%]"
                    @blur="touchLastName()"
                />
            </div>

            <Input
                v-model="email"
                type="email"
                name="email"
                label="Email"
                placeholder="jean@exemple.com"
                :error="emailError"
                :disabled="loading"
                @blur="touchEmail()"
            />

            <Input
                v-model="password"
                type="password"
                name="password"
                label="Mot de passe"
                placeholder="8 caractères minimum"
                :error="passwordError"
                :disabled="loading"
                @blur="touchPassword()"
            />

<<<<<<< HEAD
            <Button
                type="submit"
                variant="primary"
                full-width
                :loading="loading"
            >
                Créer mon compte
            </Button>
        </form>

        <Text as="p" class="text-center">
            Déjà un compte ?
            <AppLink variant="brand" to="/auth/login">Se connecter</AppLink>
        </Text>
    </Card>
=======
      <Button
        type="submit"
        variant="primary"
        full-width
        :loading="loading"
      >
        Créer mon compte
      </Button>
    </form>

    <Text as="p" class="text-center">
      Déjà un compte ?
      <AppLink variant="brand" to="/auth/login">Se connecter</AppLink>
    </Text>
  </Card>
>>>>>>> 24b007e (feat(auth): replace inline error with toast notifications on signup)
</template>
