import { ref } from 'vue'
import axios from 'axios'
import { useRoute } from '@/lib/route'

export interface ImageOption {
  value: number
  label: string
  thumb: string | null
  private?: boolean
}

/**
 * The pool of images an edit screen can pick from.
 *
 * The server sends this list once with the page. Anything dropped on a
 * picker afterwards has to land in the same list, or the freshly uploaded
 * image would not be selectable until a reload.
 */
export function useImageLibrary(initial: ImageOption[]) {
  const options = ref<ImageOption[]>([...initial])

  function add(option: ImageOption): ImageOption {
    const existing = options.value.findIndex((o) => o.value === option.value)

    if (existing === -1) {
      // Newest first: it is almost certainly the one about to be picked.
      options.value.unshift(option)
    } else {
      options.value[existing] = option
    }

    return option
  }

  function find(id: number | null): ImageOption | undefined {
    return id === null ? undefined : options.value.find((o) => o.value === id)
  }

  return { options, add, find }
}

function cookie(name: string): string | null {
  const match = document.cookie.match(new RegExp(`(^|; )${name}=([^;]*)`))

  return match ? decodeURIComponent(match[2]) : null
}

/**
 * Posts one file and gets the new image back.
 *
 * This deliberately does not go through Inertia. A dropzone lives inside a
 * form that is halfway filled in, and an Inertia post would answer with a
 * redirect and throw that away. Plain XHR keeps the page where it is.
 */
export function useImageUpload() {
  const route = useRoute()
  const uploading = ref(false)
  const progress = ref(0)
  const error = ref<string | null>(null)

  async function upload(file: File, imageType = 'photo'): Promise<ImageOption | null> {
    error.value = null
    uploading.value = true
    progress.value = 0

    const body = new FormData()
    body.append('file', file)
    body.append('image_type', imageType)
    body.append('name', file.name.replace(/\.[^.]+$/, ''))

    try {
      const { data } = await axios.post<ImageOption>(route('admin.images.upload'), body, {
        headers: { 'X-XSRF-TOKEN': cookie('XSRF-TOKEN') ?? '' },
        onUploadProgress: (event) => {
          progress.value = event.total ? Math.round((event.loaded / event.total) * 100) : 0
        },
      })

      return data
    } catch (thrown: unknown) {
      error.value = message(thrown, file)

      return null
    } finally {
      uploading.value = false
      progress.value = 0
    }
  }

  /** Whatever went wrong, say it in words the person can act on. */
  function message(thrown: unknown, file: File): string {
    if (axios.isAxiosError(thrown)) {
      const errors = thrown.response?.data?.errors as Record<string, string[]> | undefined

      if (errors?.file?.[0]) return errors.file[0]
      if (thrown.response?.status === 413) return `${file.name} is too big to upload.`
      if (thrown.response?.data?.message) return String(thrown.response.data.message)
    }

    return `${file.name} would not upload. Try again.`
  }

  return { upload, uploading, progress, error }
}
