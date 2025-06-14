import { body } from "express-validator";

const addBookValidator = [
  body("title")
    .notEmpty()
    .withMessage("Book title is required"),

  // body("author")
  //   .notEmpty()
  //   .withMessage("Author is required"),

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

  body("genre")
    .notEmpty()
    .withMessage("Book genre is required"),

  // body("coverImage")
  //   .notEmpty()
  //   .withMessage("Cover image is required")
  //   .matches(/\.(jpg|jpeg|png|gif|webp)$/i)
  //   .withMessage("Cover image must be a valid image format (.jpg, .png, .webp, etc.)"),

  // body("file")
  //   .notEmpty()
  //   .withMessage("Digital file is required")
  //   .matches(/\.(pdf|epub|mobi)$/i)
  //   .withMessage("File must be in .pdf, .epub, or .mobi format"),

  body("price")
    .notEmpty()
    .withMessage("Price is required")
    .isFloat({ gt: 0 })
    .withMessage("Price must be a number greater than 0"),
];

const updateBookValidator = [
  body("title")
    .notEmpty()
    .withMessage("Book title is required"),

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

  body("genre")
    .notEmpty()
    .withMessage("Book genre is required"),

  body("price")
    .notEmpty()
    .withMessage("Price is required")
    .isFloat({ gt: 0 })
    .withMessage("Price must be a number greater than 0"),
];

const patchBookValidator = [
  body("title")
    .optional()
    .notEmpty()
    .withMessage("Book title cannot be empty"),

  body("isbn")
    .optional()
    .notEmpty()
    .withMessage("ISBN number cannot be empty"),

  body("publishedDate")
    .optional()
    .isISO8601()
    .withMessage("Published date must be a valid date"),

  body("category")
    .optional()
    .notEmpty()
    .withMessage("Book category cannot be empty"),

  body("genre")
    .optional()
    .notEmpty()
    .withMessage("Book genre cannot be empty"),

  body("price")
    .optional()
    .isFloat({ gt: 0 })
    .withMessage("Price must be a number greater than 0"),
];

export { addBookValidator, updateBookValidator, patchBookValidator  };