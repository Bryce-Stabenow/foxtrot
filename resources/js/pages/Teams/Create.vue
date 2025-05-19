<template>
  <Head title="Create Team" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
      <h1 class="text-2xl font-semibold">Create Team</h1>

      <Card class="max-w-2xl">
        <CardHeader>
          <CardTitle>Team Details</CardTitle>
          <CardDescription>Create a new team to collaborate with others</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Team Name</Label>
              <Input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Enter team name"
                :class="{ 'border-destructive': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-sm text-destructive">
                {{ form.errors.name }}
              </p>
            </div>

            <div class="flex justify-end gap-4">
              <Button
                type="button"
                variant="outline"
                @click="router.visit(route('teams.index'))"
              >
                Cancel
              </Button>
              <Button type="submit" :disabled="form.processing">
                Create Team
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const form = useForm({
  name: '',
});

const submit = () => {
  form.post(route('teams.store'));
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Teams',
    href: route('teams.index'),
  },
  {
    title: 'Create Team',
    href: route('teams.create'),
  },
];
</script> 