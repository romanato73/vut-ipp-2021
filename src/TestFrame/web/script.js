let buttons = document.querySelectorAll('.btn');
let details = document.querySelectorAll('.details');

buttons.forEach(button => {
  button.addEventListener('click', () => {
    let id = button.dataset.id
    let detail = document.querySelector(`.details[data-id='${id}']`)

    details.forEach((item) => {
      if (item !== detail) item.style.display = "none"
    })

    if (window.getComputedStyle(detail).display === "none") {
      detail.style.display = "table-row"
    } else {
      detail.style.display = "none"
    }
  })
})

function filterBy(type) {
  let failedRows = document.querySelectorAll(`tr[data-type='FAILED']`)
  let okRows = document.querySelectorAll(`tr[data-type='OK']`)

  details.forEach(detail => detail.style.display = "none")

  if (type === "failed") {
    okRows.forEach(row => row.style.display = "none")
    failedRows.forEach(row => row.style.display = "table-row")
  } else if (type === "ok") {
    okRows.forEach(row => row.style.display = "table-row")
    failedRows.forEach(row => row.style.display = "none")
  } else {
    okRows.forEach(row => row.style.display = "table-row")
    failedRows.forEach(row => row.style.display = "table-row")
  }
}