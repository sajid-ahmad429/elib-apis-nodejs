import { body } from "express-validator";

const addBookValidator = [
  body("title")
    .notEmpty()
    .withMessage("Book title is required"),

  body("author")
    .notEmpty()
    .withMessage("Author name is required"),

  body("isbn")
    .notEmpty()
    .withMessage("ISBN number is required"),

  body("publishedDate")
    .notEmpty()
    .withMessage("Published date is required")
    .isISO8601()
    .withMessage("Published date must be a valid date"),

  body("category")
    .notEmpty()
    .withMessage("Book category is required"),

  body("price")
    .notEmpty()
    .withMessage("Price is required")
    .isFloat({ gt: 0 })
    .withMessage("Price must be a number greater than 0"),
];

export { addBookValidator };