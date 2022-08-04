<template>
  <div class="panel">
    <k-headline size="medium" class="headline">{{ headline }}</k-headline>


    <div class="wrapper">
      <k-headline size="medium" style="margin-bottom: 1rem; text-align: center">
        <button @click.prevent="changeMonth(-1)">&larr;</button>


        <div class="cv-month-year">{{ currentMonthName }} {{ currentYear }}</div>

        <button @click.prevent="changeMonth(1)">&rarr;</button>

      </k-headline>

      <div class="cv-month">
        <div class="cv-day cv-day-placeholder" v-for="i in weekDayOfFirstDayInMonth"></div>
        <div class="cv-day" :class="{'cv-loading': loading}" v-for="day in daysInMonth">
          <div class="cv-toolbar">
            <div class="cv-day-number"> {{ day.number }}</div>

            <button class="cv-add" @click.prevent="addPage(day.fullDate)">➕</button>
          </div>


          <div class="cv-day-post" v-for="post in posts[day.fullDate]" :class="`status-${post.status}`">
            <button @click.prevent="$go(post.panelLink)">{{ post.title }}</button>
          </div>

        </div>

      </div>
    </div>


    <k-dialog ref="dialog" @submit="$refs.form.submit()">
      <k-form
          ref="form"
          v-model="page"
          :fields="{
        title: {
          label: 'Title',
          type: 'text'
        }
      }"
          @submit="submitPage"
      />
    </k-dialog>

  </div>

</template>

<style lang="scss">
.headline {
  background: var(--color-gray-300);
  padding: 10px 20px;
}

.panel {
  background: var(--color-white);
  border-radius: var(--rounded);
  box-shadow: var(--shadow);
  padding-bottom: 0;
}

.wrapper {
  padding: 20px;
}

.cv-month {
  display: flex;
  flex-wrap: wrap;
  width: 100%;

  border-top: 1px solid #000;
  border-left: 1px solid #000;

}

.cv-month-year {
  display: inline-block;
  width: 200px;
}

.cv-day {
  width: calc(100% / 7);
  padding: .5rem;

  border-right: 1px solid #000;
  border-bottom: 1px solid #000;

  min-height: 150px;
  max-height: 150px;

  overflow-y: scroll;

}

.cv-day.cv-loading {
  will-change: background-color;
  animation: cv-load 500ms alternate infinite;
}

@for $i from 0 through 40 {
  .cv-day:nth-child(#{$i}) {
    animation-delay: calc(#{$i} * 50ms);
  }
}


.cv-day:last-child {
  border-right: 1px solid #000;
}


.cv-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.cv-day-number {
  font-size: 85%;
  font-weight: bold;
  margin-bottom: .5em;
}

.cv-day-post button {
  display: block;
  font-size: 75%;
  margin-bottom: .5em;
  line-height: 145%;

  background-color: #eee;
  padding: .25rem;

  &:hover {
    background-color: #fefefe;
  }
}

.cv-day-post.status-unlisted {
  font-style: italic;
}

.cv-day-post.status-draft {
  font-style: italic;
  opacity: 0.5;
}

@keyframes cv-load {
  0% {
    background-color: transparent;
  }

  100% {
    background-color: #ccc;
  }
}

</style>

<script>
export default {
  computed: {
    weekDayOfFirstDayInMonth() {
      if (!this.currentDate) {
        return 0;
      }

      return parseInt(this.$library.dayjs(this.currentDate).date(1).format('d'));
    },

    currentMonth() {
      if (!this.currentDate) {
        return;
      }

      return this.$library.dayjs(this.currentDate).format('MM');
    },

    currentMonthName() {
      if (!this.currentDate) {
        return;
      }

      return this.$library.dayjs(this.currentDate).format('MMMM');
    },

    currentYear() {
      if (!this.currentDate) {
        return;
      }

      return this.$library.dayjs(this.currentDate).format('YYYY');
    },

    lastDayInMonth() {
      if (!this.currentDate) {
        return;
      }

      return this.$library.dayjs(this.currentDate).endOf('month').format('YYYY-MM-DD');
    },

    daysInMonth() {
      if (!this.currentDate) {
        return 0;
      }


      return Array.from(Array(this.$library.dayjs(this.currentDate).daysInMonth()).keys())
          .map(i => {
                return {
                  fullDate: `${this.currentYear}-${this.currentMonth}-${String(i + 1).padStart(2, '0')}`,
                  number: i + 1
                }
              }
          );
    }

  },
  methods: {

    changeMonth(direction = 1) {
      this.currentDate = this.$library.dayjs(this.currentDate).add(direction * 1, 'month').date(1).format('YYYY-MM-DD')
    },


    addPage(date) {
      this.page.date = date;
      this.$refs.dialog.open()
    },

    submitPage() {
      const target = this.target.replace('[YEAR]', this.$library.dayjs().format("YYYY"));

      this.$api.pages.create(target, {
        slug: this.$helper.slug(this.page.title),
        title: this.page.title,
        template: this.defaultTemplate,
        content: {
          title: this.page.title,
          text: '',
          date: this.page.date
        }
      }).then(data => {
        this.$refs.dialog.close()
        this.$go(`pages/${data.id.replace(/\//g, '+')}`);
      })
    }

  },

  data() {
    return {
      headline: '',
      parentPage: '',
      target: '',
      defaultTemplate: '',

      loading: false,
      currentDate: null,
      page: {title: null, date: null},
      posts: []
    }
  },

  watch: {
    currentDate() {
      this.loading = true;
      this.$api.get(`calendar-view/${this.parentPage}/${this.currentDate}/${this.lastDayInMonth}`).then(({data}) => {
        this.loading = false;

        this.posts = {}
        data.forEach(post => {
          if (!this.posts[post.date]) {
            this.posts[post.date] = [];
          }

          this.posts[post.date].push(post);
        })

      })
    }
  },


  created() {
    this.load().then(response => {
      this.headline = response.headline;
      this.parentPage = response.parent;
      this.target = response.target;
      this.defaultTemplate = response.defaultTemplate;

      this.currentDate = this.$library.dayjs().date(1).format('YYYY-MM-DD');
    });

  }
};
</script>
